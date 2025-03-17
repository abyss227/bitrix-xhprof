define('ENABLE_XHPROF', true);

if (ENABLE_XHPROF && function_exists('xhprof_enable')) {
    global $getMicrotime;

    $getMicrotime = function () {
        list($usec, $sec) = explode(' ', microtime());
        return doubleval($usec) + doubleval($sec);
    };

    xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
    $GLOBALS['xhprof_start_time'] = $getMicrotime();

    register_shutdown_function(function () {
        global $getMicrotime;
        $xhprof_data = xhprof_disable();
        $tStart = $GLOBALS['xhprof_start_time'];
        $diffTime = $getMicrotime() - $tStart;

        if ($diffTime > 1) {
            $timeF = str_replace('.', '-', round($diffTime, 3) . "");
            $client = $_SERVER['SHELL'] ? 'shell' : 'http';
            $uri = $_SERVER['REQUEST_URI'] ? $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] : $_SERVER['PHP_SELF'];
            $uri = implode('|', explode('/', $uri));
            $xhprofCode = preg_replace('#[^a-z0-9_|-]#i', '_', "{$client}_{$uri}_{$timeF}s");

            $pathXhprof = '/usr/share/xhprof';
            include_once "$pathXhprof/xhprof_lib/utils/xhprof_lib.php";
            include_once "$pathXhprof/xhprof_lib/utils/xhprof_runs.php";

            $xhprof_runs = new XHProfRuns_Default();
            $xhprof_runs->save_run($xhprof_data, $xhprofCode);
        }
    });
}
