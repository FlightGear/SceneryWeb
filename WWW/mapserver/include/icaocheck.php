<?php
  # Ensure ICAO code is valid
  if(strlen($icao) == 0)
  {
    die ("Please enter valid ICAO code");
  }
  # Limit ICAO strings to 5 characters
  if(strlen($icao) > 5)
  {
    die ("ICAO code too long !");
  }
?>
