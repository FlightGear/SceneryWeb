<br />

<center>Version
<?php

// When was it last updated?

$filename = '../../.git/ORIG_HEAD';
$result = file_get_contents($filename);
echo substr($result,0,7);

if (file_exists($filename))
{
echo "&nbsp;-&nbsp;" . date("F d Y H:i", filemtime($filename));
}

?>
 - <a href="https://www.gitorious.org/fg/sceneryweb/commits/master">Version info</a> - <a href="../../TOBEDONE">Volunteer ?</a> - <a href="../../README">README</a> - <a href="../../LICENCE">License</a> - <a href="../../VERSION">History</a>
</center>
</body>
</html>
