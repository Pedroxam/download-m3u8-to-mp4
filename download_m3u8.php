<?php
if(!isset($_GET['dl'])) die('?');

if (substr(php_uname(), 0, 7) == "Windows")
{
	define('FFMPEG_PATH', './ffmpeg.exe');  // visit ffmpeg site for download this.
}
else
{
	define('FFMPEG_PATH', exec('which ffmpeg'));  // or '/usr/bin/ffmpeg'.
}

$url = trim($_GET['dl']);

download_m3u8($url);

function download_m3u8($url, $dir = '')
{
    $content = file_get_contents($url);

    if (preg_match_all('/(http|https):\/\/.*/', $content, $matches) or preg_match_all('/.+\.ts/', $content, $matches))
	{
        if (!$dir) {
            $dir = "video/" . md5($url);
        }
		
        makedir($dir);
		
        echo "dir {$dir}\n\n";
        echo "download ts\n";
		
        $count = count($matches[0]);
		
        foreach ($matches[0] as $key => $value) {
            if (strpos($value, 'http') === false) {
                $parse_url_result = parse_url($url);
                $url_path = $parse_url_result['path'];
                $arr = explode('/', $url_path);
                array_splice($arr, -1);
                $url_path_pre = $parse_url_result['scheme'] . "://" . $parse_url_result['host'] . implode('/', $arr) . "/";
                $value = $url_path_pre . $value;
            }
			
            $ts_output = "{$dir}/{$key}.ts";
            $cmd = "curl -L -o {$ts_output} '{$value}'";
			
			echo betterExec($cmd); //exec cmd
			
            echo "\n$cmd\n";
			
            if (is_file($ts_output))
			{
                $ts_outputs[] = $ts_output;
            }
			else {
				//update
				makedir($ts_output);
            }
        }
        if ($count > 100) {
            $to_concat = array_chunk($ts_outputs, 100);
        } else {
            $to_concat[] = $ts_outputs;
        }
		
        echo "concat ts to mp4\n";
		
        print_r($to_concat);
		
        foreach ($to_concat as $key => $value) {
			
            $str_concat = implode('|', $value);
			
            $mp4_output = "{$dir}/output{$key}.mp4";
			
            $cmd = FFMPEG_PATH . " -i \"concat:{$str_concat}\" -acodec copy -vcodec copy -absf aac_adtstoasc {$mp4_output}";
			
			echo betterExec($cmd); //exec cmd
			
            echo "\n$cmd\n";
			
            if (is_file($mp4_output))
			{
                $mp4_outputs[] = $mp4_output;
            }
			else
			{
                echo "create mp4_outputs file failed ;\n $cmd";
                exit();
            }
        }
        $last = "{$dir}/output.mp4";
		
        if (count($to_concat) > 1) {
			$fileliststr = '';
            foreach ($mp4_outputs as $key => $value)
			{
                $fileliststr .= "file '{$value}'\n";
            }
			
			$filelist_file = dirname(__FILE__) . "/filelist.txt";
			
            file_put_contents($filelist_file, $fileliststr);
			
			$cmd = FFMPEG_PATH . " -f concat -i $filelist_file -c copy $path";
			
			echo betterExec($cmd); //exec cmd
			
        }
		else
		{
            $mp4_output = "{$dir}/output{$key}.mp4";
			
            rename($mp4_output, $last);
        }

        if (is_file($last)) {
            $cmd = "rm -rf {$dir}/*ts";
			echo betterExec($cmd); //exec cmd
            echo "\n$cmd\n";

            echo "\n\nsuccess {$last}\n";
        }
		else {
            echo "\n\nfailed\n";
        }
    }
}

function betterExec($cmd)
{
	$log = dirname(__FILE__) . '/logs.txt';
		
	//windows
	if (substr(php_uname(), 0, 7) == "Windows")
	{
		pclose(popen("start /B " . $cmd . " 1> $log 2>&1", "r"));
	}
	//linux
	else
	{
		shell_exec($cmd . " 1> $log 2>&1");
	}
	
	return file_get_contents($log); //return log
}


function makedir($dir)
{
    return is_dir($dir) or (makedir(dirname($dir)) and mkdir($dir, 0777));
}
