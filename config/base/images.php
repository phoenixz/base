<?php
// Imagemagic configuration
$_CONFIG['images'] = array('imagemagic'         => array('convert'          => '/usr/bin/convert',                          // The location of the imagemagic "convert" command
                                                         'nice'             => 11,                                          // imagemagick process "convert" nice level
                                                         'strip'            => true,                                        // Should exif information be stripped or not
                                                         'blur'             => 0.01,                                        // gaussian blur of % of image size to reduce jpeg image size
                                                         'interlace'        => 'auto-plane',                                // Type of interlace to apply, use one of none, gif, png, jpeg, line, partition, plane, empty (default, plane), auto-*. auto will use the * (* must be one of none, gif, png, jpg, plane, partition, or empty) on files > 10KB, and no interleave on files < 10KB.
                                                         'sampling_factor'  => '4:2:0',                                     // This option specifies the sampling factors to be used by the JPEG encoder for chroma downsampling. Current setting reduces the chroma channel's resolution to half, without messing with the luminance resolution that your eyes latch onto
                                                         'quality'          => 70,                                          // JPEG image quality to apply
                                                         'keep_aspectratio' => true,                                        // If set to true, if width and / or height was omitted, the image aspect ratio will be preserved while doing resizes
                                                         'defines'          => array('jpeg:dct-method=float'),              // use the more accurate floating point discrete cosine transform, rather than the default fast integer version

                                                         'limit'            => array('memory' => 32,                        // Memory limit (in MB)
                                                                                     'map'    => 32)));                     // Map limit (in MB)

?>
