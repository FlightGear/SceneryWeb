<?php
  # Ensure ICAO code is valid
  if(strlen($icao) == 0)
  {
    die ("Please enter valid ICAO code");
  }
  # Limit ICAO strings to 4 characters
  if(strlen($icao) > 4)
  {
    die ("ICAO code too long !");
  }
?>
