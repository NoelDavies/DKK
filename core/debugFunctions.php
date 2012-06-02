<?php
/*======================================================================*\
||              Cybershade CMS - Your CMS, Your Way                     ||
\*======================================================================*/
if(!defined('INDEX_CHECK')){ die('Error: Cannot access directly.'); }

    /**
     * Pretty wrapper to print_r()
     *
     * @version    1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   variable     $var
     * @param   string      $info
     *
     * @return  string
     */
    function dump(&$var, $info = false) {
        if (file_exists('debug')) { return; }
        $scope = false;
        $prefix = 'unique';
        $suffix = 'value';

        $vals = ($scope ? $scope : $GLOBALS);

        $old = $var;
        $var = $new = $prefix.rand().$suffix;
        $vname = false;
        foreach($vals as $key => $val) {
            if ($val === $new) {
                $vname = $key;
            }
        }
        $var = $old;

        $debug = debug_backtrace();
        $call_info = array_shift($debug);
        $code_line = $call_info['line'];
        $file = explode((stristr(PHP_OS, 'WIN') ? '\\' : '/'), $call_info['file']);
        $file = array_pop($file);

        $return = '';

        $return .= '<pre class="debug"><b>('.$file.' : '.$code_line.')</b>';

        if ($info != false) {
            $return .= ' | <b style="color: red;">'.$info.':</b>';
        }
        $return .= '<br />'.doDump($var, '$'.$vname);
        $return .= '</pre>';
        return $return;
    }

    /**
     * Internal function used with dump();
     *
     * @access     private
     * @version    2.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   variable     $var
     * @param   string      $var_name
     * @param   string      $indent
     * @param   string      $reference
     *
     * @return  string
     */
    function doDump(&$var, $var_name = null, $indent = null, $reference = null) {
        $do_dump_indent = '<span style="color:#eeeeee;">|</span> &nbsp;&nbsp; ';
        $reference = $reference.$var_name;
        $keyvar = 'the_do_dump_recursion_protection_scheme';
        $keyname = 'referenced_object_name';
        $return = '';

        if (is_array($var) && isset($var[$keyvar])) {
            $real_var = &$var[$keyvar];
            $real_name = &$var[$keyname];
            $type = ucfirst(gettype($real_var));
            $return .= $indent.$var_name.'<span style="color:#a2a2a2">'.$type.'</span> = <span style="color:#e87800;">&amp;'.$real_name.'</span><br />';
        } else {
            $var = array($keyvar => $var, $keyname => $reference);
            $avar = &$var[$keyvar];

            $type = ucfirst(gettype($avar));
            if ($type == 'String') {
                $type_color = '<span style="color:green">';
            } elseif($type == 'Integer') {
                $type_color = '<span style="color:red">';
            } elseif($type == 'Double') {
                $type_color = '<span style="color:#0099c5">'; $type = 'Float';
            } elseif($type == 'Boolean') {
                $type_color = '<span style="color:#92008d">';
            } elseif($type == 'null') {
                $type_color = '<span style="color:black">';
            } elseif($type == 'Resource') {
                $type_color = '<span style="color:#00c19f">';
            }

            $keyNames = array('[\'password\']', '[\'pin\']');
            $avar = in_array($var_name, $keyNames) ? str_pad('', (strlen($avar)), '*') : $avar;
            if (is_array($avar)) {
                $count = count($avar);
                $return .= $indent.($var_name ? $var_name.'=> ' : '').'<span style="color:#a2a2a2">'.$type.'('.$count.')</span><br />'.$indent.'(<br />';
                $keys = array_keys($avar);
                foreach($keys as $name) {
                    $value = &$avar[$name];
                    $return .= doDump($value, "['$name']", $indent.$do_dump_indent, $reference);
                }
                $return .= "$indent)<br />";
            } elseif(is_object($avar)) {
                $return .= "$indent$var_name <span style='color:#a2a2a2'>$type</span><br />$indent(<br />";
                $_indent = $indent.$do_dump_indent;
                foreach($avar as $key => $value){
                    $return .= "$_indent$key <span style='color:#a2a2a2'>$type</span><br />$_indent(<br />$_indent)<br />";
                }
                $return .= "$indent)<br />";
            } elseif(is_int($avar)) {
                $return .= "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> ".
                                "$type_color$avar</span><br />";
            } elseif(is_string($avar)) {
                $return .= "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> ".
                                "$type_color\"".str_replace(str_split("\t\n\r\0\x0B"), '', htmlspecialchars($avar))."\"</span><br />";
            } elseif(is_float($avar)) {
                $return .= "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> ".
                                "$type_color$avar</span><br />";
            } elseif(is_bool($avar)) {
                $return .= "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> ".
                                "$type_color".($avar == 1 ? "true" : "false")."</span><br />";
            } elseif(is_null($avar)) {
                $return .= "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> {$type_color}NULL</span><br />";
            } elseif(is_resource($avar)) {
                $return .= "$indent$var_name = <span style='color:#a2a2a2'>$type</span> $type_color$avar</span><br />";
            } else {
                $return .= "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> ".
                                "$avar<br />";
            }
            $var = $var[$keyvar];

            return $return;
        }
    }

    /**
     * Determine where a function is being called from
     *
     * @version    1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string     $info
     * @param   string  $nl
     *
     * @return  string
     */
    function getExecInfo($info = null, $nl = '<br />') {
        $a = debug_backtrace();

        $msg = array();
        $x = 0;
        foreach($a as $key => $file) {
            $msg[] = outputDebug($file, ($x==0 ? $info : null), $nl);
            $x++;
        }
        return implode('', $msg).$nl;
    }

    /**
     * Output a specfic iteration for getExecInfo
     *
     * @version    1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   array     $file
     * @param   string     $info
     * @param   string  $nl
     *
     * @return  string
     */
    function outputDebug($file, $info = null, $nl='<br />') {
        $filename = explode((stristr(PHP_OS, 'WIN') ? '\\' : '/'), $file['file']);
        $msg = ($info !== null ? '<strong>['.$info.']</strong> <br />' : null).
                    ' Called on line <strong>'.$file['line'].'
                        </strong> of file <strong>'.$filename[count($filename) - 1].
                        '</strong> via function <strong>'.$file['function'].
                        '</strong> with arguments: (\''.
                        (is_array($file['args'])
                            ? secureMe(implode('\', \'', $file['args']))
                            : null).
                        '\')'.$nl;

        return $msg;
    }

    /**
     * Calculates Memory useage and Execution time between calls
     *
     * @version    1.0
     * @since   1.0.0
     * @author  xLink
     *
     * @param   string     $info
     * @param   string  $nl
     *
     * @return  string
     */
    function memoryUsage($info = null, $nl = '<br />') {
        static $start_time = null;
        static $start_code_line = 0;

        $debug = debug_backtrace();
        $call_info = array_shift($debug);
        $code_line = $call_info['line'];
        $file = explode((stristr(PHP_OS, 'WIN') ? '\\' : '/'), $call_info['file']);
        $file = array_pop($file);

        if ($start_time === null) {
            print 'debug ['.($info === null ? null : $info).']<strong>'.$file.'</strong>> init'.$nl;
            $start_time = time() + microtime();
            $start_code_line = $code_line;
            return 0;
        }

        printf('debug [%s]<strong>%s</strong>> [ <strong>%d-%d</strong> ] Exec: <strong>%.4f</strong> Memory: <strong>%s</strong>'.$nl,
            ($info === null ? null : $info),
            $file,
            $start_code_line,
            $code_line,
            (time() + microtime() - $start_time),
            formatBytes(memory_get_usage())
        );
        $start_time = time() + microtime();
        $start_code_line = $code_line;
    }

/**
 * CMS's Error functionality, these will prettify, and give you detailed error reporting
 * when this file is included and functional
 */
    function error_handler($errno, $errstr, $errfile, $errline) {
        if(!(error_reporting() & $errno)){ return; }

        if($errno == 8){ return; }
        $exception = new error_Exception($errstr, $errno, $errfile, $errline);
        exception_handler($exception);
    }

    function exception_handler($exception) {
        include ('exception.php');
    }

    function fatal_error_handler() {
        if ($error = error_get_last()) {
            switch($error['type']) {
                case E_PARSE:
                case E_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                    $exception = new error_Exception($error['message'], $error['type'], $error['file'], $error['line']);
                    include ('exception.php');
                    break;
            }
        }
        exit;
    }



    abstract class cmsError extends Exception {
        /**
         * The variables used for template
         */
        public $_message;
        public $_code;
        public $_class;
        public $_file;
        public $_line;
        public $_trace;
        public $_source;

        /**
         * Indicates the type of exception
         * @var string (error / exception)
         */
        public $_type = 'exception';

        /**
         * Sets variables accordingly
         *
         * @param string $message The exception message
         * @param int $code The exception code
         * @param Exception $previous
         */
        public function __construct($message, $code = 0, exception $previous = null) {
            // defaults
            if (!isset($this->_message)){ $this->_message = $message; }
            if (!isset($this->_code)){ $this->_code = $code != 0 ? $this->readable($code) : null; }
            if (!isset($this->_class)){ $this->_class = get_class($this); }
            if (!isset($this->_file)){ $this->_file = $this->getFile(); }
            if (!isset($this->_line)){ $this->_line = $this->getLine(); }
            if (!isset($this->_trace)){ $this->_trace = $this->getStack(); }
            if (!isset($this->_source)){ $this->_source = $this->getSource(@file($this->_file), $this->_line, 0); }

            // preserve the construct
            parent::__construct($message, $code, $previous);
        }

        /**
         * turn error codes into something readable
         *
         * @param int $code The exception code
         *
         * @return string
         */
        private function readable($code) {
            if ($this->_type == 'exception'){ // extend this for exceptions as well ?
                     return null;
            }

            $definition = array(
                                E_ERROR => 'Error',
                                E_WARNING => 'Warning',
                                E_PARSE => 'Parsing Error',
                                E_NOTICE => 'Notice',
                                E_CORE_ERROR => 'Core Error',
                                E_CORE_WARNING => 'Core Warning',
                                E_COMPILE_ERROR => 'Compile Error',
                                E_COMPILE_WARNING => 'Compile Warning',
                                E_USER_ERROR => 'User Error',
                                E_USER_WARNING => 'User Warning',
                                E_USER_NOTICE => 'User Notice',
                                E_STRICT => 'Runtime Notice',
                                E_RECOVERABLE_ERROR => 'Catchable Fatal Error',
                                E_DEPRECATED => 'Deprecated',
                                E_USER_DEPRECATED => 'User Deprecated'
                                );

            return $definition[$code];
        }

        /**
         * Get source and highlight it for output
         *
         * @param string $source Line source
         * @param int $error Error on line
         * @param int $level (0 = error / 1 = warn)
         * @param int $lines Source lines to show
         *
         * @return string
         */
        private function getSource($source, $error, $level = 0, $lines = 10) {
            $output = null;
            $found = false;
            $begin = $e = $error - $lines > 0 ? $error - $lines : 1;
            $end = $error + $lines <= count($source) ? $error + $lines : count($source);
            $mark = $level == 0 ? 'error' : 'warn';

            // colorize
            foreach($source as $idx => &$line) {
                $colorize = null;

                if (preg_match('/\/\*/', $line)){ $found = true; }// fix comments
                if (preg_match('/<\?(php)?[^[:graph:]]/', $line)) {
                    $colorize .= str_replace(array('<code>', '</code>'), '', highlight_string($line, true)); // fix colors
                } else {
                    if ($found) {
                        $colorize .= preg_replace(array('/(&lt;\?php&nbsp;)+/', '/\/\//'), '', str_replace(array('<code>', '</code>'), array(''), highlight_string('<?php //'.$line, true))); // fix comment
                    } else {
                        $colorize .= preg_replace('/(&lt;\?php&nbsp;)+/', '', str_replace(array('<code>', '</code>'), array(''), highlight_string('<?php '.$line, true))); // fix colors
                    }
                }
                if (preg_match('/\*\//', $line)) $found = false; // end fix comments

                // output the marked line or the normal lines
                if (($idx + 1) === $error) {
                    $line = "<tr><td><div class='{$mark}' style='padding-left: 5px;'>".($idx + 1).".</div></td><td><div class='{$mark}' style='padding-left: 5px;'>{$colorize}</div></td></tr>";
                } else {
                    $line = "<tr><td style='padding-left: 5px; color: #000000;'>".($idx + 1).".</td><td style='padding-left: 5px;'>{$colorize}</td></tr>";
                }
            }

            // only get a certain number of lines to show
            for($i = $begin - 1; $i < $end; $i++) {
                $output .= $source[$i];
            }

            return "<table cellpadding='0' cellspacing='0' style='width: 100%; line-height: 15px; font-size: 0.95em; font-family:Verdana;'><col width='1%' /><col width='99%' />{$output}</table>";
        }

        /**
         * html pretty stack trace
         *
         * @return string
         */
        private function getStack() {
            $output = null;
            $trace = $this->getTrace();

            // remove traces that dont give a file and line parameter
            // also remove traces that are the same with the source
            foreach($trace as $idx => $line) {
                if (!isset($line['file']) && !isset($line['line'])) {
                    unset($trace[$idx]);
                } else
                    if ($line['file'] == $this->_file && $line['line'] == $this->_line) {
                        unset($trace[$idx]);
                    }
            }

            $i = count($trace);
            foreach($trace as $line) {
                $line['class'] = isset($line['class']) ? $line['class'] : null;
                $line['type'] = isset($line['type']) ? $line['type'] : null;

                $output .= "<div id='in_{$i}' title='expand stack #{$i}'><span class='number'>#{$i}.</span><span class='func'>{$line['class']}{$line['type']}{$line['function']}()</span><small>{$line['file']} (line: {$line['line']})</small></div>";
                $output .= "<div id='out_{$i}' style='display: none;'><div class='indent'>".$this->getSource(@file($line['file']), $line['line'], 1, 5)."</div></div>";

                $i--;
            }

            return strlen($output) == 0 ? "<small>No backtrace avalible.</small>" : $output;
        }
    }

    class error_Exception extends cmsError {
        /**
         * @param string $message The error message
         * @param int $code The error code
         * @param string $filename The error file
         * @param int $lineno The error line
         */
        public function __construct($message, $code, $filename, $lineno) {
            $this->_file = $filename;
            $this->_line = $lineno;
            $this->_type = 'error';

            // dont forget about the parent
            parent::__construct($message, $code);
        }
    }
?>