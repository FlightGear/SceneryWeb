// This script is here to check for the consistency of the different fields of the form

function checkNumeric(objName,minval,maxval,period)
{
    var numberfield = objName;
    if (chkNumeric(objName,minval,maxval,period) == false)
    {
        numberfield.select();
        numberfield.focus();
        return false;
    }
    else
    {
        return true;
    }
}

function chkNumeric(objName,minval,maxval,period)
{
    var checkOK = "-0123456789.";
    var checkStr = objName;
    var allValid = true;
    var decPoints = 0;
    var allNum = "";

    for (i = 0;  i < checkStr.value.length;  i++)
    {
        ch = checkStr.value.charAt(i);
        for (j = 0;  j < checkOK.length;  j++)
            if (ch == checkOK.charAt(j))
                break;
        if (j == checkOK.length)
        {
            allValid = false;
            break;
        }
        if (ch != ",")
            allNum += ch;
    }
    
    if (!allValid)
    {
        alertsay = "Please enter only the values :\""
        alertsay = alertsay + checkOK + "\" in the \"" + checkStr.name + "\" field."
        alert(alertsay);
        return (false);
    }

    // Sets minimum and maximums
    var chkVal = allNum;
    var prsVal = parseInt(allNum);
    if (chkVal != "" && !(prsVal >= minval && prsVal <= maxval))
    {
        alertsay = "Please enter a value greater than or "
        alertsay = alertsay + "equal to \"" + minval + "\" and less than or "
        alertsay = alertsay + "equal to \"" + maxval + "\" in the \"" + checkStr.name + "\" field."
        alert(alertsay);
        return (false);
    }
}
