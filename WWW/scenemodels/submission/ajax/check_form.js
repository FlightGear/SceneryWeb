// This script is here to check for the consistency of the different fields of the form

var numbers = "0123456789";
var letters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

function checkNumeric(numberfield, minval, maxval, period)
{
    if (chkNumeric(numberfield, minval, maxval,period) == false)
    {
        numberfield.select();
        numberfield.focus();
        return false;
    }

    return true;
}

function chkNumeric(objName, minval, maxval, period)
{
    var checkOK = numbers+ "-.";
    var checkStr = objName;
    var allValid = true;
    //var decPoints = 0;
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

    return true;
}

function checkComment(textfield)
{
    if (chkComment(textfield.value) == false)
    {
        alertsay = "Please enter only letters, numbers, and punctuation marks";
        alertsay = alertsay + " in the \"" + textfield.name + "\" field.";
        alert(alertsay);

        textfield.select();
        textfield.focus();
        return false;
    }

    return true;
}

function chkComment(checkStr)
{
    var checkOK = numbers + letters + ",;:!?@' ";
    var allValid = true;
    var allNum = "";

    for (i = 0;  i < checkStr.length;  i++)
    {
        ch = checkStr.charAt(i);
        for (j = 0;  j < checkOK.length;  j++)
            if (ch == checkOK.charAt(j))
                break;
        if (j == checkOK.length)
        {
            allValid = false;
            break;
        }
    }

    return allValid;
}

function checkEmail(emailfield)
{
    if (chkEmail(emailfield) == false)
    {
        emailfield.select();
        emailfield.focus();
        return false;
    }

    return true;
}

function chkEmail(emailfield)
{
    var checkOK = numbers + letters + "@_-.";
    var checkStr = emailfield.value;
    var allValid = true;
    var allNum = "";

    if(checkStr.length == 0)
        return true;

    for (i = 0;  i < checkStr.length;  i++)
    {
        ch = checkStr.charAt(i);
        for (j = 0;  j < checkOK.length;  j++)
            if (ch == checkOK.charAt(j))
                break;
        if (j == checkOK.length)
        {
            allValid = false;
            break;
        }
    }

    if(!allValid)
    {
        alertsay = "Please enter only letters, numbers, '@', '_', '-' and '.'";
        alertsay = alertsay + " in the \"" + emailfield.name + "\" field.";
        alert(alertsay);

        return false;
    }

    //Checks if the value looks like an email adress
    var numberOfAt = 0;
    var numberOfPointAfterAt = 0;
    for (i = 0;  i < checkStr.length;  i++)
    {
        ch = checkStr.charAt(i);

        if(ch=='@')
            numberOfAt++;
        if(numberOfAt>=1 && ch=='.')
            numberOfPointAfterAt++;
    }

    if(numberOfAt != 1 || numberOfPointAfterAt<1)
    {
        allValid = false;
        alert("This is not a valid email adress!");
    }

    return allValid;
}

function checkSTG(textfield)
{
    if (chkSTG(textfield.value) == false)
    {
        alertsay = "Please enter only letters, spaces, numbers, underscores, - and /";
        alertsay = alertsay + " in the \"" + textfield.name + "\" field.";
        alert(alertsay);

        textfield.select();
        textfield.focus();
        return false;
    }

    return true;
}

function chkSTG(checkStr)
{
    var checkOK = numbers + letters + "_-./ ";
        var checkOK = numbers + letters + ",;:!?@' ";
    var allValid = true;
    var allNum = "";

    for (i = 0;  i < checkStr.length;  i++)
    {
        ch = checkStr.charAt(i);
        for (j = 0;  j < checkOK.length;  j++)
            if (ch == checkOK.charAt(j))
                break;
        if (j == checkOK.length)
        {
            allValid = false;
            break;
        }
    }

    return allValid;
}
