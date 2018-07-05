<?php

function video_library_init(){
    try{
        if(!safe_exec('which ffmpeg')){
            throw new bException(tr('video: ffmpeg module not installed, run this command on your server: sudo apt update && sudo apt install ffmpeg libav-tools x264 x265;'), 'not_available');
        }

        load_libs('file');

    }catch(Exception $e){
        throw new bException('video_library_init(): Failed', $e);
    }
}

function get_video_thumbnail($video_path, $size='50x50'){
    try{
        $output_path = PUBTMP;
        if (!file_exists($output_path)) {
            mkdir($output_path);
        }
        $output_path .= substr(md5(strval(round(microtime(true) * 1000))), 0, 6) . '.jpeg';

        $complete_command = "ffmpeg -i {$video_path} -deinterlace -an -ss 00:00:01 -t 00:00:02 -s {$size} -r 1 -y -vcodec mjpeg -f mjpeg {$output_path} 2>&1";
        safe_exec($complete_command, 0);
        return $output_path;
    }catch(Exception $e){
        throw new bException('get_video_thumbnail(): Failed', $e);
    }
}
