#!/usr/bin/env php
<?php
date_default_timezone_set ( 'PRC' );

//$x = $_SERVER;
//file_put_contents ( "/da0/logs/supervisor.log", date ( 'Y-m-d H:i:s' ) . '|' . var_export ( $x, true ), FILE_APPEND );

if(! isset($_SERVER['SUPERVISOR_SERVER_URL']))
{
	write_stderr ( "not in supervisor\n" );
	exit;
}

if (! defined ( "STDIN" )) 
{
	write_stderr ( "not in stdin\n" );
	exit;
}

while ( 1 ) {
	
	// transition from ACKNOWLEDGED to READY
	write_stdout ( "READY\n" );
	
	// read header line and print it to stderr
	$line = fgets(STDIN);
	write_stderr ( $line );
	
	//parse
	$str = '';
	$headers = parseData($line, $str);
	write_stderr ( $str );
	
	// read event payload and print it to stderr
	// headers = dict([ x.split(':') for x in line.split() ])
	// data = sys.stdin.read(int(headers['len']))
	// fread($handle);
	$data = fread ( STDIN, ( int ) $headers ['len'] );
	
	write_stderr ( $data );
	
	$null = '';
	$myarr = parseData($data, $null);
	
	$cmd = "/sbin/ifconfig eth1 |grep inet|head -1|awk '{print $2}'";
	$ip = exec($cmd);
	$text = "supervisor进程退出报警：";
	$text .= "IP:{$ip},任务{$myarr['processname']}(pid={$myarr['pid']})从状态'{$myarr['from_state']}'退出";
	//processname:collect groupname:collect from_state:RUNNING expected:0 pid:1900
	
	//http
	$data = array(
			'chat_type'=> 0,
			'chat_id' => yourChatId,
			'text' => $text
	);
	$query = http_build_query($data);
	$options['http'] = array(
			'timeout'=> 3,
			'method' => 'POST',
			'header' => 'Content-type:application/x-www-form-urlencoded',
			'content' => $query
	);
	$context = stream_context_create($options);
	$url = "https://botapi.chaoxin.com/sendTextMessage/yourTokenId";
	$result = file_get_contents($url, false, $context);
	
	// transition from READY to ACKNOWLEDGED
	write_stdout ( "RESULT 2\nOK" );
}

function parseData($line, &$str = '')
{
	$arr = explode ( " ", $line );
	// print_r($arr);
	
	$str = '';
	$headers = array ();
	foreach ( $arr as $val ) {
		list ( $key, $item ) = explode ( ":", $val );
		$str .= "&{$key}={$item}";
		$headers [$key] = $item;
	}
	return $headers;
}
function write_stdout($s) {
	//$s = date ( 'Y-m-d H:i:s' ) . '|stdout|' . $s;
	fwrite ( STDOUT, $s );
	flush();
}
function write_stderr($s) {
	$s = date ( 'Y-m-d H:i:s' ) . '|stderr|' . $s;
	fwrite ( STDERR, $s );
	flush();
}
