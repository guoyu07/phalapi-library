<?php
/**
 * 用于开发命令行应用的扩展类库
 *
 * - Example
```
    $cli = new CLI_Lite();
    $cli->response();
```
 *
 * - Usage
 ```
    ./cli -s Default.Index --username dogstar
``` 
 *
 * @author dogstar <chanzonghuang@gmail.com> 20170205
 */

require_once dirname(__FILE__) . '/Ulrichsg/Getopt/Getopt.php';
require_once dirname(__FILE__) . '/Ulrichsg/Getopt/Option.php';
require_once dirname(__FILE__) . '/Ulrichsg/Getopt/Argument.php';
require_once dirname(__FILE__) . '/Ulrichsg/Getopt/CommandLineParser.php';
require_once dirname(__FILE__) . '/Ulrichsg/Getopt/OptionParser.php';

use Ulrichsg\Getopt\Getopt;
use Ulrichsg\Getopt\Option;
use Ulrichsg\Getopt\Argument;

class CLI_Lite {

    public function response() {
        // 解析获取service参数
        $serviceOpt = new Option('s', 'service', Getopt::REQUIRED_ARGUMENT);
        $serviceOpt->setDescription('接口服务');

        $getopt = new Getopt(array(
            $serviceOpt,
            new Option(NULL, 'help'),
        ));

        $service = NULL;
        try {
            $getopt->parse();

            $service = $getopt['service'];
            if ($service === NULL) {
                echo $getopt->getHelpText();
                echo "\n\nError: 缺少service参数'\n";
                exit(1);
            }
        } catch (UnexpectedValueException $e) {
            // just go ahead ...
        }

        // 再转换处理 。。。
        try{
            // 获取接口实例
            $rules = array();
            try {
                DI()->request = new PhalApi_Request(array('service' => $service));
                $api = PhalApi_ApiFactory::generateService(false);
                $rules = $api->getApiRules();
            } catch (PhalApi_Exception $ex){
                throw new UnexpectedValueException($ex->getMessage());
            }

            // PhalApi接口参数转换为命令行参数
            $rule2opts = array();
            foreach ($rules as $ruleKey => $ruleItem) {
                $opt = new Option(null, $ruleItem['name'], !empty($ruleItem['require']) ? Getopt::REQUIRED_ARGUMENT : Getopt::OPTIONAL_ARGUMENT);

                if (isset($ruleItem['default'])) {
                    $opt->setArgument(new Argument($ruleItem['default']));
                }

                if (isset($ruleItem['desc'])) {
                    $opt->setDescription($ruleItem['desc']);
                }

                $rule2opts[] = $opt;
            }

            // 添加参数选项，提取命令行参数并重新注册请求
            $getopt->addOptions($rule2opts);

            $getopt->parse();

            if ($getopt['help']) {
                echo $getopt->getHelpText();
                exit(1);
            }

            DI()->request = new PhalApi_Request($getopt->getOptions());

            // 转交PhalApi重新响应处理
            $api = new PhalApi();
            $rs = $api->response();
            $rs->output();
        } catch (UnexpectedValueException $e) {
            echo $getopt->getHelpText();
            echo "\n\nError: ".$e->getMessage()."\n";
            exit(1);
        }
    }
}
