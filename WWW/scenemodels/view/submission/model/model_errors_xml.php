<?php
/**
 * This script creates an xml file containing the country code
 */

header('Content-Type: text/xml');
?>
<?xml version="1.0" standalone="yes" ?>
<errors>
<?php
    if (isset($errors)) {
        foreach ($errors as $error) {
            echo "<error>".$error->getMessage()."</error>";
        }
    }
?>
</errors>
