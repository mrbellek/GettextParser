<?php

require_once('GettextParserAdapter.php');
require_once('GettextParserPattern.php');

class GettextParser
{
    /**
     * @var GettextParserAdapter
     */
    protected $adapter;

    /**
     * base path
     *
     * @var string
     */
    protected $basePath;

    /**
     * log file path
     *
     * @var string
     */
    protected $logPath;

    /**
     * result destination path
     *
     * @var string
     */
    protected $resultPath;

    /**
     * @var array
     */
    protected $phrasesList = array();

    /**
     * @var array
     */
    protected $filesList = array();

    /**
     * @var string
     */
    protected $xgettextDir = 'C:\Program Files (x86)\Poedit\bin';


    /**
     * @param $adapterName
     *
     * @throws Exception
     */
    public function __construct($adapterName)
    {
        $this->basePath = realpath(__DIR__);

        //init files
        $this->logPath = $this->basePath . '/log.txt';
        $this->resultPath = sys_get_temp_dir() . '/poedit_' . $adapterName . '_' . md5(microtime()) . '.php';

        if (is_string($adapterName)) {
            $this->loadAdapter($adapterName);
        } else {
            throw new Exception('AdapterName not specified');
        }
    }

    /**
     * loads adapter
     *
     * @param $adapterName
     *
     * @throws Exception
     *
     * @return void
     */
    protected function loadAdapter($adapterName)
    {
        $targetClassName = 'GettextParserAdapter_' . $adapterName;
        $targetFilePath
            = $this->basePath . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $targetClassName) . ".php";

        if (is_file($targetFilePath)) {
            require_once($targetFilePath);
            $this->adapter = new $targetClassName;
        } else {
            throw new Exception("Cannot load Adapter {$targetClassName}");
        }
    }

    /**
     * @return GettextParserAdapter
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * performs files parsing and generates output for poEdit parser
     *
     * @param $params
     *
     * @return void
     */
    public function run($params)
    {
        $this->log(implode(' ', $params));

        $this->processParams($params);
        $this->parse();

        if (!count($this->phrasesList)) {
            $this->log('Nothing found!');
        }

        if ($this->writeOutput()) {
            $this->executePoEditParser($params);
        }
    }

    /**
     * processes params and sets some variables
     *
     * @param $params
     *
     * @return void
     */
    protected function processParams($params)
    {
        $this->filesList = array();

        $paramsCount = count($params);
        for ($k = 7; $k < $paramsCount; $k++) {
            $this->filesList[] = $params[$k];
        }
    }

    /**
     * @return void
     */
    protected function parse()
    {
        $this->phrasesList = array();

        foreach ($this->filesList as $filePath) {
            if (is_readable($filePath)) {
                $phrases = $this->getAdapter()->parse(file_get_contents($filePath));
                if (is_array($phrases)) {
                    $this->phrasesList = array_merge($this->phrasesList, $phrases);
                }
            } else {
                $this->log("Cannot read file {$filePath}" . PHP_EOL);
            }
        }
    }

    /**
     * returns true on success
     *
     * @return bool
     */
    protected function writeOutput()
    {
        //$this->log( print_r( $this->_phrases_list, true ) );
        $gettextCallsBuffer = '';

        foreach ($this->phrasesList as $phrase) {
            if (is_array($phrase)) {
                //plural
                $gettextCallsBuffer .= 'ngettext(';
                foreach ($phrase as $idx => $item) {
                    if ($idx > 0) {
                        $gettextCallsBuffer .= ', ';
                    }
                    $gettextCallsBuffer .= '"' . $item . '"';
                }
                $gettextCallsBuffer .= ', 3);' . PHP_EOL;
            } else {
                //single
                $gettextCallsBuffer .= '_("' . $this->escapeQuotes($phrase) . '");' . PHP_EOL;
            }
        }

        $result = "<?php" . PHP_EOL . "/*" . PHP_EOL . implode(PHP_EOL, $this->filesList) . "*/";
        $result .= str_repeat(PHP_EOL, 2) . $gettextCallsBuffer;

        return ( bool )file_put_contents($this->resultPath, $result, FILE_BINARY);
    }

	private function escapeQuotes($phrase)
	{
		//text will be printed in double quotes, so
		//- escape any double quotes in text that are not already escaped
		//- unescape any escaped single quotes

		if (strpos($phrase, '\"') === FALSE) {
			$phrase = str_replace('"', '\"', $phrase);
		}

		$phrase = str_replace("\'", "'", $phrase);

		return $phrase;
	}

    /**
     * @param $params
     */
    protected function executePoEditParser($params)
    {
        chdir($this->xgettextDir);

        $cmd = 'xgettext.exe --force-po -o "' . $params[2] . '" ' . $params[3] . ' ' . $params[4] . ' "'
            . $this->resultPath . '"';

        $this->log($cmd);

        exec($cmd);
    }

    /**
     * writes messages to log
     *
     * @param $message
     *
     * @return void
     */
    protected function log($message)
    {
        $f = fopen($this->logPath, 'a');
        fwrite($f, $message . PHP_EOL);
        fclose($f);
    }
}