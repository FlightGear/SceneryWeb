// This script is here to check for the consistency of the different fields of the form

var numbers = "0123456789";
var letters = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";

function emptyDefaultValue(field, defaultValue)
{
    if (field.value == defaultValue)
        field.value = "";
}

function checkNumeric(numberfield, minval, maxval)
{
    if (!chkNumeric(numberfield, minval, maxval))
    {
        numberfield.select();
	numberfield.focus();
	return false;
    }

    return true;
}

function chkNumeric(objName, minval, maxval)
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
        checkStr.style.border = "2px solid rgb(200, 0, 0)";
        return false;
    }

    // Sets minimum and maximums
    var chkVal = allNum;
    var prsVal = parseInt(allNum);
    if (chkVal != "" && !(prsVal >= minval && prsVal <= maxval))
    {
        checkStr.style.border = "2px solid rgb(200, 0, 0)";
        return false;
    }
    
    checkStr.style.border = "2px solid rgb(0, 200, 0)";
    return true;
}

function checkComment(textfield)
{
    if (!chkComment(textfield.value))
    {
        textfield.style.border = "2px solid rgb(200, 0, 0)";
        return false;
    } else if (textfield.value != "") {
        textfield.style.border = "2px solid rgb(0, 200, 0)";
    } else {
        textfield.style.border = "";
    }

    return true;
}

function chkComment(checkStr)
{
    var checkOK = numbers + letters + ";:!?@-_. ";
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
    if (!chkEmail(emailfield))
    {
        emailfield.select();
        emailfield.focus();
        emailfield.style.border = "2px solid red";
        return false;
    } else {
        emailfield.style.border = "2px solid green";
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
    }

    return allValid;
}

function checkSTG(textfield)
{
    if (!chkSTG(textfield.value))
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
    var checkOK = numbers + letters + "_-./ \r\n";
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

function checkFilename(objName)
{
    if (!chkFilename(objName.value))
    {
        objName.select();
        objName.focus();
        return false;
    }

    return true;
}

function chkFilename(checkStr)
{
    var checkOK = numbers + letters + ".-_";
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
            alertsay = "File name only accept letters, numbers, '.', '-' and '_'!";
            alert(alertsay);
            allValid = false;
            break;
        }
    }

    return allValid;
}

function checkStringNotDefault(field, defaultValue)
{
    if (field.value == defaultValue)
    {
        alertsay = "Please change the value of the " + field.name + " field!";
        alert(alertsay);

        field.focus();
        return false;
    }

    return true;
}
