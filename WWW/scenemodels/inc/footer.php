<div style="text-align:center;">
<br />
<?php

// What's the last GIT version of the website and when was it last updated?

$filename = '/home/fgscenery/WWW/.git/ORIG_HEAD';
if (file_exists($filename))
{
    $result = file_get_contents($filename);
    echo "Version&nbsp;".substr($result,0,7)."&nbsp;-&nbsp;" . date("F d Y H:i", filemtime($filename)) ."&nbsp;-&nbsp;";
}

?>
<a href="https://www.gitorious.org/fg/WWW/commits/master">Version info</a> - <a href="../../TOBEDONE">Volunteer ?</a> - <a href="../../README">README</a> - <a href="../../LICENCE">License</a> - <a href="../../VERSION">History</a>
</div>
</body>
</html>
