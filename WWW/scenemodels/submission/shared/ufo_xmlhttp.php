<?php

function print_r_xml($mixed)
{
    // capture the output of print_r
    $out = print_r($mixed, true);

    // Replace the root item with a struct
    // MATCH : '<start>element<newline> ('
    $root_pattern = '/[ \t]*([a-z0-9 \t_]+)\n[ \t]*\(/i';
    $root_replace_pattern = '<struct name="root" type="\\1">';
    $out = preg_replace($root_pattern, $root_replace_pattern, $out, 1);

    // Replace array and object items structs
    // MATCH : '[element] => <newline> ('
    $struct_pattern = '/[ \t]*\[([^\]]+)\][ \t]*\=\>[ \t]*([a-z0-9 \t_]+)\n[ \t]*\(/miU';
    $struct_replace_pattern = '<struct name="\\1" type="\\2">';
    $out = preg_replace($struct_pattern, $struct_replace_pattern, $out);
    // replace ')' on its own on a new line (surrounded by whitespace is ok) with '</var>
    $out = preg_replace('/^\s*\)\s*$/m', '</struct>', $out);

    // Replace simple key=>values with vars
    // MATCH : '[element] => value<newline>'
    $var_pattern = '/[ \t]*\[([^\]]+)\][ \t]*\=\>[ \t]*([a-z0-9 \t_\S]+)/i';
    $var_replace_pattern = '<var name="\\1">\\2</var>';
    $out = preg_replace($var_pattern, $var_replace_pattern, $out);

    $out =  trim($out);

    return $out;
}

$handle = fopen("tmp/data".$_SERVER['REMOTE_ADDR'].".xml", "w+");
$content = print_r_xml(@file_get_contents("php://input"));
fwrite($handle,$content);
fclose($handle);

?>