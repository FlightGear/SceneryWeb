<?php

// Connects Read Only to the database
// ==================================

function connect_sphere_r()
{
	// Inserting dependencies and defining settings
	
	include("/home/ojacq/.scenemodels");
	$dbrname = $database;
	$dbrhost = $host;
	$dbruser = $ro_user;
	$dbrpass = $ro_pass;
	
	// Connecting silently
	
	$resource_r = @pg_connect('dbname='.$dbrname.' host='.$dbrhost.' user='.$dbruser.' password='.$dbrpass.' sslmode=disable');
		
	// If could not connect to the database
	
	if ($resource_r=='0')
	{
	echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\"";
	echo "\"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">";
	echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">";
	echo "<head>";
	echo "<title>Automated Shared Models Positions Update Form</title>";
	echo "<meta http-equiv=\"Content-Type\" content=\"text/html;charset=utf-8\" />";
	echo "<link rel=\"stylesheet\" href=\"../../style.css\" type=\"text/css\"></link>";
	echo "</head>";
	echo "<body>";
	echo "<?php include '../../header.php'; ?>";
	echo "<br /><br />";
	echo "\n<font color=\"red\">We're sorry, but an error has occurred while connecting to the database.</font>\n";
	exit;
	}
	else
	{
	// Returning resource_r

	return($resource_r);
	 }
}

// Connects Read-Write to the database
// ===================================

function connect_sphere_rw()
{
	// Inserting dependencies and defining settings
	
	include("/home/ojacq/.scenemodels");
	$dbrwname = $database;
	$dbrwhost = $host;
	$dbrwuser = $rw_user;
	$dbrwpass = $rw_pass;
	
	// Connecting silently
	
	$resource_rw = @pg_connect('dbname='.$dbrwname.' host='.$dbrwhost.' user='.$dbrwuser.' password='.$dbrwpass.' sslmode=disable');

	// If could not connect to the database
	
	if ($resource_rw=='0')
	{
	echo "\n<font color=\"red\">An error has occurred while connecting to the database.</font>\n";
	exit;
	}
	else
	{
	// Returning resource_rw
	
	return($resource_rw);
	}
}

// Returns the name of the family sent as parameter
// ================================================

function family_name($id_family)
{
	$mg_id=pg_escape_string($id_family);
	
	// Connecting to the database.
	
	$headerlink_family = connect_sphere_r();
	
	// Querying...
	
	$query = "select mg_id,mg_name from fgs_modelgroups where mg_id='".$mg_id."';";
	$result = @pg_query($headerlink_family,$query);
	
	while($row = @pg_fetch_assoc($result))
	{
    $name_family=$row["mg_name"];
	}

	// Closing the connection.

	@pg_close($headerlink_family);

	return($name_family);
}


// Returns the name of the object sent as parameter
// ================================================

function object_name($id_object)
{
	
	$mg_id=pg_escape_string($id_object);

	// Connecting to the databse.
	
	$headerlink_object = connect_sphere_r();

	// Querying...

	$query = "select mo_id,mo_name from fgs_models where mo_id='".$mg_id."';";
	$result = @pg_query($headerlink_object,$query);

	// Showing the results.

	while($row = @pg_fetch_assoc($result))
	{
    $name_object=$row["mo_name"];
	}

	// Closing the connection.

	@pg_close($headerlink_object);

	return($name_object);
}

// Returns the name of the family of an ob_id sent as parameter
// ============================================================

function family_name_from_object_id($ob_id)
{
	$mg_id=pg_escape_string($ob_id);
	
	// Connecting to the database.
	
	$headerlink_family = connect_sphere_r();
	
	// Querying...
	
	$query1 = "select ob_model from fgs_objects where ob_id=".$ob_id.";";
	$result = @pg_query($headerlink_family,$query1);
	
	while($row = @pg_fetch_assoc($result)) {
	$mo_id=$row["ob_model"];
	$query2 = "select mo_shared from fgs_models where mo_id=".$mo_id.";";
	$result2 = @pg_query($headerlink_family,$query2);
		
		while($row2 = @pg_fetch_assoc($result2))
		{
		$mg_family = $row2["mo_shared"];
		return(family_name($mg_family));
		}
	}

	// Closing the connection.

	@pg_close($headerlink_family);
}

// Returns the object model from an ob_id sent as parameter
// ========================================================

function object_model_from_object_id($ob_id)
{
	$mg_id=pg_escape_string($ob_id);
	
	// Connecting to the database.
	
	$headerlink_family = connect_sphere_r();
	
	// Querying...
	
	$query1 = "select ob_model from fgs_objects where ob_id=".$ob_id.";";
	$result = @pg_query($headerlink_family,$query1);
	
	while($row = @pg_fetch_assoc($result))
	{
    $mo_id=$row["ob_model"];
	}

	// Closing the connection.

	@pg_close($headerlink_family);
	
	return($mo_id);
}

// Get the object elevation from an ob_id sent as parameter
// ========================================================

function get_elevation_from_id($ob_id)
{
	
	// Connecting to the database.
	
	$headerlink_family = connect_sphere_r();
	
	// Querying...
	
	$query = "select ob_gndelev from fgs_objects where ob_id=".$ob_id.";";
	$result = @pg_query($headerlink_family,$query);
	
	while($row = @pg_fetch_assoc($result))
	{
    	return $row["ob_gndelev"];
	}

	// Closing the connection.

	@pg_close($headerlink_family);	
}

// Get the object offset from an ob_id sent as parameter
// =====================================================

function get_offset_from_id($ob_id)
{
	
	// Connecting to the database.
	
	$headerlink_family = connect_sphere_r();
	
	// Querying...
	
	$query = "select ob_elevoffset from fgs_objects where ob_id=".$ob_id.";";
	$result = @pg_query($headerlink_family,$query);
	
	while($row = @pg_fetch_assoc($result))
	{
	if(($row["ob_elevoffset"])=="") {
	return(0);
	}
	else return($row["ob_elevoffset"]);
	}

	// Closing the connection.

	@pg_close($headerlink_family);	
}

// Get the true object orientation from an ob_id sent as parameter
// ===============================================================

function get_true_orientation_from_id($ob_id)
{
	
	// Connecting to the database.
	
	$headerlink_family = connect_sphere_r();
	
	// Querying...
	
	$query = "select ob_heading from fgs_objects where ob_id=".$ob_id.";";
	$result = @pg_query($headerlink_family,$query);
	
	while($row = @pg_fetch_assoc($result))
	{
    	return $row["ob_heading"];
	}

	// Closing the connection.

	@pg_close($headerlink_family);	
}

// Returns the number of objects in the database.
// ==============================================

function count_objects()
{

	// Connecting to the databse.
	
	$resource = connect_sphere_r();

	// Count the number of objects in the database

	$counter = @pg_query($resource,"select count(*) as rows from fgs_objects;");

	while ($line = @pg_fetch_assoc($counter))
	{
	echo number_format($line['rows'], '0', '', ' ');
	}

// Close the database resource

@pg_close($resource);
}

// Returns the number of models in the database.
// =============================================

function count_models()
{

	// Connecting to the databse.
	
	$resource = connect_sphere_r();

	// Count the number of objects in the database

	$counter = @pg_query($resource,"select count(*) as rows from fgs_models;");

	while ($line = @pg_fetch_assoc($counter))
	{
	echo number_format($line['rows'], '0', '', ' ');
	}

// Close the database resource

@pg_close($resource);
}

// Checks the availability of the database.
// ========================================

function check_availability()
{
	// Connecting to the database.

	$resource = connect_sphere_r();

	if($resource!='0')
		{
		// Close the database resource

		@pg_close($resource);
		
		// Say everything is OK
		
		return(1);
		}
	else
		{
		// Close the database resource

		@pg_close($resource);
		
		// Apologies
		
		return(0);
		}
}

// Computes the STG heading into a true heading before submission to the database.
// ===============================================================================

function heading_stg_to_true($stg_heading)
{
	if($stg_heading > '180')
	{
	$true_heading = 540 - $stg_heading;
	}
	else
	{
	$true_heading = 180 - $stg_heading;
	}
	return($true_heading);
}

// Computes the true heading into a STG heading (for edition purposes).
//=====================================================================

function heading_true_to_stg($true_heading)
{
	if($true_heading > '180')
	{
	$stg_heading = 540 - $true_heading;
	}
	else
	{
	$stg_heading = 180 - $true_heading;
	}
	return($stg_heading);
}

// Check if models exists in DB from a model name sent in parameter.
// =================================================================
// Model's name is composed of: OBJECT_SHARED Models/
// a mg_path from fgs_modelgroups;
// a mo_path from fgs_objects;
// ie : Models/Power/windturbine.xml
// So we have to check that the couple Power/windturbine.xml exists: if both concatenated values are ok, then we're fine.

function model_exists($model_name)
{
	// Starting by checking the existence of the object
	
	$mg_id=pg_escape_string($model_name);
	$tab_path = explode("/",$mg_id); 				// Explodes the fields of the string separated by /
	$max_tab_path = count($tab_path);				// Counts the number of fields.
	$queried_mo_path=$tab_path[$max_tab_path-1];			// Returns the last field value.
	
	// Checking that the label "Model" is correct
	
	if(strcmp($tab_path[0],"Models")) { return(1); exit; }		// If ever dumb people try to put something else here.
	
	// Connecting to the database.
	
	$headerlink_family = connect_sphere_rw();
	
	// Querying...
	
	$query = "select mo_path, mo_shared from fgs_models where mo_path = '".$queried_mo_path."';";
	$result = @pg_query($headerlink_family,$query);
	
	// Checking the number of results. Should be 1.
	
	if(@pg_num_rows($result) == 1) 					// If object is known, going to check the family next.
	{	
		// Now proceeding with the family
		// The family path is the string between Models and the object name. Can be multiple.
	
		for($j=1;$j<($max_tab_path-1);$j++)
		{
		$queried_family_path.=$tab_path[$j]."/";
		}
	
		// Querying to check the existence of the family
		
		$query_family = "select mg_path from fgs_modelgroups where mg_path='".$queried_family_path."';";
		$result_family = pg_query($headerlink_family,$query_family);
		
		if(@pg_num_rows($result_family) == 1)	// If the family & model are known, return 0.
		{
		return(0);
		}
		else
		{
		return(3);
		exit;
		}	// If the family is unknown, I say it and exit
	}
	else
	{ 
	return(2);
	exit; }		// Il the object is unknown, I say it and exit
		
	// Closing the connection.

	@pg_close($headerlink_family);
}

// Returns an ob_model id from a model name sent in parameter.
// ===========================================================

function ob_model_from_name($model_name)
{	
	$mg_id=pg_escape_string($model_name);
	$tab_path = explode("/",$mg_id); 						// Explodes the fields of the string separated by /
	$max_tab_path = count($tab_path);						// Counts the number of fields.
	$queried_mo_path=$tab_path[$max_tab_path-1];					// Returns the last field value.
		
	// Connecting to the database.
	
	$headerlink = connect_sphere_rw();
	
	// Querying...
	
	$query = "select mo_id, mo_path from fgs_models where mo_path = '".$queried_mo_path."';";
	$result = @pg_query($headerlink,$query);
	
	// Checking the number of results. Should be 1.
	
	if(@pg_num_rows($result) == 1) // If object is known, returning the mo_id.
	{	
		while ($row = pg_fetch_row($result)) { return($row[0]); }		
	}
	
	// Closing the connection.

	@pg_close($headerlink);
}

// List the authors of models in FlightGear.
// =========================================

function list_authors()
{	
	// Connecting to the database.
	
	$headerlink_authors = connect_sphere_r();
	
	// Querying...
	
	$query = "select au_id,au_name from fgs_authors order by 2 asc;";
	$result = @pg_query($headerlink_authors,$query);
	
	while($row = @pg_fetch_assoc($result))
	{
	if($row["au_id"]==1) echo "<option value=\"".$row["au_id"]."\" selected>".$row["au_name"]."</option>\n";
    else echo "<option value=\"".$row["au_id"]."\">".$row["au_name"]."</option>\n";
	}
	
	// Closing the connection.

	@pg_close($headerlink_family);

}

// List the countries in FlightGear.
// =================================

function list_countries()
{	
	// Connecting to the database.
	
	$headerlink_countries = connect_sphere_r();
	
	// Querying...
	
	$query = "select * from fgs_countries order by 2 asc;";
	$result = @pg_query($headerlink_countries,$query);
	
	while($row = @pg_fetch_assoc($result))
	{
    echo "<option value=\"".$row["co_code"]."\">".$row["co_name"]."</option>\n";
	}
	
	// Closing the connection.

	@pg_close($headerlink_countriers);

}

// Returns the extension of a file sent in parameter

function ShowFileExtension($filepath)
{
        preg_match('/[^?]*/', $filepath, $matches);
        $string = $matches[0];
     
        $pattern = preg_split('/\./', $string, -1, PREG_SPLIT_OFFSET_CAPTURE);       
        
		if(count($pattern) > 1) 
        {
            $filenamepart = $pattern[count($pattern)-1][0];
            preg_match('/[^?]*/', $filenamepart, $matches);
            return($matches[0]);
        }
}

// Deletes a directory sent in parameter

function clearDir($dossier)
{
  $ouverture=@opendir($dossier);
  if (!$ouverture) return;
  while($fichier=readdir($ouverture)) {
    if ($fichier == '.' || $fichier == '..') continue;
    if (is_dir($dossier."/".$fichier)) {
      $r=clearDir($dossier."/".$fichier);
      if (!$r) return false;
    }else{
      $r=@unlink($dossier."/".$fichier);
      if (!$r) return false;
    }
  }
  closedir($ouverture);
  $r=@rmdir($dossier);
  if (!$r) return false;
  return true;
}

?>
