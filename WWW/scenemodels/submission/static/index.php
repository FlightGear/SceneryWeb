<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
  <title>Automated Static Models Submission Form</title>
  <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
  <link rel="stylesheet" href="../../style.css" type="text/css"></link>
</head>
  <?php include '/home/jstockill/scenemodels/header.php'; ?>

<body onload='update_objects();'>

<!-- <h3><font color="red">Service limited due to planned database maintenance.</font></h3> -->
<script language="JavaScript">
<!-- This script is here to check for the consistency of the different fields of the form -->

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
//  End -->
</script>
<p>

<h1 align=center>Static Models Automated Submission Form</h1>
<b>Foreword:</b> This automated form goal is to ease the submission of static models into FG Scenery database. There are currently 2 477 models in <a href="http://scenemodels.flightgear.org/models.php">our database</a>. Please help us to make it more!

Please read <a href="http://scenemodels.flightgear.org/contribute.php">this page</a> in order to understand what recommandations this script is looking for. Please note that all fields are now mandatory.
<br /><br />
Note this page is under HEAVY DEVELOPMENT and links to nowhere. Please do NOT use it unless we ask you for. It'll be for a bright future.<br/><br/>
<span style="color:red;">Files <u>must have the same name</u> except for thumbnail file. i.e: XXXX_thumbnail.png (thumbnail file), XXXX.ac (AC3D file), XXXX.xml (XML file), XXXX.png (texture file)</span>
<br /><br />
<form name="positions" method="POST" action="check_static.php" enctype="multipart/form-data">
<input type="hidden" name="MAX_FILE_SITE" value="2000000" />
<table>
	<tr>
		<td><span title="This is the model path name, ie the name as it's supposed to appear in the .stg file."><a style="cursor: help; ">Model path name</a></span></td>
		<td>
			<input type=text name ="mo_path">
		</td>
	</tr>
	<tr>
		<td><span title="This is the name of the author. If the author does not exist, please ask the scenery mantainers to add it."><a style="cursor: help; ">Author</a></span></td>
		<td>
			<select name="mo_author">
			<option value="73">Alain Laurent</option>
<option value="34">Alex Bamesreiter</option>
<option value="52">Alex Park</option>
<option value="81">Alexander Raldugin</option>
<option value="22">Alexis Bory</option>
<option value="42">Anders Gidenstam</option>
<option value="43">Andi Boesch</option>
<option value="84">Andre Dietrich</option>
<option value="68">Andrew Keeble</option>
<option value="25">Bertrand Augras</option>
<option value="32">Bertrand Gilot</option>
<option value="40">Billy Harrison</option>
<option value="29">Carsten Vogel</option>
<option value="6">Chris Metzler</option>
<option value="47">Christian Schmitt</option>
<option value="66">Cristian Marchi</option>
<option value="89">Curtis Olson</option>
<option value="31">Daniel Leygnat</option>
<option value="5">Dave Martin</option>
<option value="58">David Culp</option>
<option value="69">David Glowsky</option>
<option value="13">David Megginson</option>
<option value="90">David van Mosselbeen</option>
<option value="86">Detlef Faber</option>
<option value="30">Dominique Lemesre</option>
<option value="77">Don Lavelle</option>
<option value="57">Emmanuel Baranger</option>
<option value="10">Erik Hofman</option>
<option value="16">Esa Hyytia</option>
<option value="54">Fahim Imaduddin Dalvi</option>
<option value="65">Francesco Angelo Brisa</option>
<option value="7">Frederic Bouvier</option>
<option value="67">Gabor Kovacs</option>
<option value="83">Gary Taverner</option>
<option value="36">Georg Vollnhals</option>
<option value="26">Gerard Robin</option>
<option value="41">Gijs de Rooy</option>
<option value="75">Harald Becker</option>
<option value="71">Heiko Schulz</option>
<option value="61">Hugo Devon</option>
<option value="12">Innis Cunningham</option>
<option value="82">Jack Mermod</option>
<option value="27">Jakub Skibinski</option>
<option value="56">Jean-Yves Le Bleu</option>
<option value="59">Jeffrey Shattuck</option>
<option value="18">Jens Thoms Toerring</option>
<option value="50">John Holden</option>
<option value="95">Jon Ortuondo</option>
<option value="2">Jon Stockill</option>
<option value="85">Jorg van der Venne</option>
<option value="15">Josh Babcock</option>
<option value="88">Julien Nguyen</option>
<option value="24">Julien Pierru</option>
<option value="98">Lukas Miersch</option>
<option value="92">Mariusz Migut</option>
<option value="19">Mark Akermann</option>
<option value="21">Martin C. Doege</option>
<option value="3">Martin Spott</option>
<option value="8">Melchior Franz</option>
<option value="55">Michel van de Mheen</option>
<option value="72">Mike Nieber</option>
<option value="11">Mike Round</option>
<option value="17">Mircea Lutic</option>
<option value="28">Morten Oesterlund Joergensen</option>
<option value="33">Morten Skyt Eriksen</option>
<option value="97">Natan Talifero</option>
<option value="35">Oliver Predelli</option>
<option value="87">Oliver Thurau</option>
<option value="80">Olivier Jacq</option>
<option value="74">Paolo Rota</option>
<option value="48">Paolo Savini</option>
<option value="38">Paul Bohnert</option>
<option value="37">Paul Richter</option>
<option value="60">Philipp Hullmann</option>
<option value="45">Polar Humenn</option>
<option value="64">Pradeep Reddy</option>
<option value="76">Rainer Fischer</option>
<option value="78">Rick A. Butler</option>
<option value="62">Robert Leda</option>
<option value="53">Robert Shearman</option>
<option value="9">Roberto Inzerillo</option>
<option value="79">Ryan Miller</option>
<option value="44">SAVAGE</option>
<option value="96">Scott Hamilton</option>
<option value="23">Sebastian Bechtold</option>
<option value="14">Stuart Buchanan</option>
<option value="51">Tapio Kautto</option>
<option value="4">Thomas Foerster</option>
<option value="91">Thomas Grossberger</option>
<option value="93">Thomas Polzer</option>
<option value="20">Torsten Dreyer</option>
<option value="1" selected>unknown</option>
<option value="63">Vic Marriott</option>
<option value="70">Vivian Meazza</option>
<option value="46">Wolfram Gottfried</option>
<option value="39">Wolfram Wagner</option>
			</select>
		</td>
	</tr>
		<tr>
		<td><span title="This is the two-letter country code where the model is located. If the author does not exist, please ask the scenery mantainers to add it."><a style="cursor: help; ">Country</a></span></td>
		<td>
<select name="ob_country">
<option value="af">Afghanistan                                       </option>
<option value="al">Albania                                           </option>
<option value="dz">Algeria                                           </option>
<option value="as">American Samoa                                    </option>
<option value="ad">Andorra                                           </option>
<option value="ao">Angola                                            </option>
<option value="ai">Anguilla                                          </option>
<option value="aq">Antarctica                                        </option>
<option value="ag">Antigua and Barbuda                               </option>
<option value="ar">Argentina                                         </option>
<option value="am">Armenia                                           </option>
<option value="aw">Aruba                                             </option>
<option value="au">Australia                                         </option>
<option value="at">Austria                                           </option>
<option value="az">Azerbaijan                                        </option>
<option value="bs">Bahamas                                           </option>
<option value="bh">Bahrain                                           </option>
<option value="bd">Bangladesh                                        </option>
<option value="bb">Barbados                                          </option>
<option value="by">Belarus                                           </option>
<option value="be">Belgium                                           </option>
<option value="bz">Belize                                            </option>
<option value="bj">Benin                                             </option>
<option value="bm">Bermuda                                           </option>
<option value="bt">Bhutan                                            </option>
<option value="bo">Bolivia                                           </option>
<option value="ba">Bosnia and Herzegowina                            </option>
<option value="bw">Botswana                                          </option>
<option value="bv">Bouvet Island                                     </option>
<option value="br">Brazil                                            </option>
<option value="io">British Indian Ocean Territory                    </option>
<option value="bn">Brunei Darussalam                                 </option>
<option value="bg">Bulgaria                                          </option>
<option value="bf">Burkina Faso                                      </option>
<option value="bi">Burundi                                           </option>
<option value="kh">Cambodia                                          </option>
<option value="cm">Cameroon                                          </option>
<option value="ca">Canada                                            </option>
<option value="cv">Cape Verde                                        </option>
<option value="ky">Cayman Islands                                    </option>
<option value="cf">Central African Republic                          </option>
<option value="td">Chad                                              </option>
<option value="cl">Chile                                             </option>
<option value="cn">China                                             </option>
<option value="cx">Christmas Island                                  </option>
<option value="cc">Cocos (Keeling) Islands                           </option>
<option value="co">Colombia                                          </option>
<option value="km">Comoros                                           </option>
<option value="cd">Congo, Democratic Republic of (was Zaire)         </option>
<option value="cg">Congo, People's Republic of                       </option>
<option value="ck">Cook Islands                                      </option>
<option value="cr">Costa Rica                                        </option>
<option value="ci">Cote d'Ivoire                                     </option>
<option value="hr">Croatia (local name Hrvatska)                     </option>
<option value="cu">Cuba                                              </option>
<option value="cy">Cyprus                                            </option>
<option value="cz">Czech Republic                                    </option>
<option value="dk">Denmark                                           </option>
<option value="dj">Djibouti                                          </option>
<option value="dm">Dominica                                          </option>
<option value="do">Dominican Republic                                </option>
<option value="tl">East Timor                                        </option>
<option value="ec">Ecuador                                           </option>
<option value="eg">Egypt                                             </option>
<option value="sv">El Salvador                                       </option>
<option value="gq">Equatorial Guinea                                 </option>
<option value="er">Eritrea                                           </option>
<option value="ee">Estonia                                           </option>
<option value="et">Ethiopia                                          </option>
<option value="fk">Falkland Islands (malvinas)                       </option>
<option value="fo">Faroe Islands                                     </option>
<option value="fj">Fiji                                              </option>
<option value="fi">Finland                                           </option>
<option value="fr">France                                            </option>
<option value="fx">France, Metropolitan                              </option>
<option value="gf">French Guiana                                     </option>
<option value="pf">French Polynesia                                  </option>
<option value="tf">French Southern Territories                       </option>
<option value="ga">Gabon                                             </option>
<option value="gm">Gambia                                            </option>
<option value="ge">Georgia                                           </option>
<option value="de">Germany                                           </option>
<option value="gh">Ghana                                             </option>
<option value="gi">Gibraltar                                         </option>
<option value="gr">Greece                                            </option>
<option value="gl">Greenland                                         </option>
<option value="gd">Grenada                                           </option>
<option value="gp">Guadeloupe                                        </option>
<option value="gu">Guam                                              </option>
<option value="gt">Guatemala                                         </option>
<option value="gn">Guinea                                            </option>
<option value="gw">Guinea-Bissau                                     </option>
<option value="gy">Guyana                                            </option>
<option value="ht">Haiti                                             </option>
<option value="hm">Heard and Mc Donald Islands                       </option>
<option value="hn">Honduras                                          </option>
<option value="hk">Hong Kong                                         </option>
<option value="hu">Hungary                                           </option>
<option value="is">Iceland                                           </option>
<option value="in">India                                             </option>
<option value="id">Indonesia                                         </option>
<option value="ir">Iran (Islamic Republic of)                        </option>
<option value="iq">Iraq                                              </option>
<option value="ie">Ireland                                           </option>
<option value="il">Israel                                            </option>
<option value="it">Italy                                             </option>
<option value="jm">Jamaica                                           </option>
<option value="jp">Japan                                             </option>
<option value="jo">Jordan                                            </option>
<option value="kz">Kazakhstan                                        </option>
<option value="ke">Kenya                                             </option>
<option value="ki">Kiribati                                          </option>
<option value="kp">Korea, Democratic People's Republic of            </option>
<option value="kr">Korea, Republic of                                </option>
<option value="kw">Kuwait                                            </option>
<option value="kg">Kyrgyzstan                                        </option>
<option value="la">Lao People's Democratic Republic                  </option>
<option value="lv">Latvia                                            </option>
<option value="lb">Lebanon                                           </option>
<option value="ls">Lesotho                                           </option>
<option value="lr">Liberia                                           </option>
<option value="ly">Libyan Arab Jamahiriya                            </option>
<option value="li">Liechtenstein                                     </option>
<option value="lt">Lithuania                                         </option>
<option value="lu">Luxembourg                                        </option>
<option value="mo">Macau                                             </option>
<option value="mk">Macedonia, The former Yugoslav Republic of        </option>
<option value="mg">Madagascar                                        </option>
<option value="mw">Malawi                                            </option>
<option value="my">Malaysia                                          </option>
<option value="mv">Maldives                                          </option>
<option value="ml">Mali                                              </option>
<option value="mt">Malta                                             </option>
<option value="mh">Marshall Islands                                  </option>
<option value="mq">Martinique                                        </option>
<option value="mr">Mauritania                                        </option>
<option value="mu">Mauritius                                         </option>
<option value="yt">Mayotte                                           </option>
<option value="mx">Mexico                                            </option>
<option value="fm">Micronesia, Federated States of                   </option>
<option value="md">Moldova, Republic of                              </option>
<option value="mc">Monaco                                            </option>
<option value="mn">Mongolia                                          </option>
<option value="ms">Montserrat                                        </option>
<option value="ma">Morocco                                           </option>
<option value="mz">Mozambique                                        </option>
<option value="mm">Myanmar                                           </option>
<option value="na">Namibia                                           </option>
<option value="nr">Nauru                                             </option>
<option value="np">Nepal                                             </option>
<option value="nl">Netherlands                                       </option>
<option value="an">Netherlands Antilles                              </option>
<option value="nc">New Caledonia                                     </option>
<option value="nz">New Zealand                                       </option>
<option value="ni">Nicaragua                                         </option>
<option value="ne">Niger                                             </option>
<option value="ng">Nigeria                                           </option>
<option value="nu">Niue                                              </option>
<option value="nf">Norfolk Island                                    </option>
<option value="mp">Northern Mariana Islands                          </option>
<option value="no">Norway                                            </option>
<option value="om">Oman                                              </option>
<option value="pk">Pakistan                                          </option>
<option value="pw">Palau                                             </option>
<option value="ps">Palestinian Territory, Occupied                   </option>
<option value="pa">Panama                                            </option>
<option value="pg">Papua New Guinea                                  </option>
<option value="py">Paraguay                                          </option>
<option value="pe">Peru                                              </option>
<option value="ph">Philippines                                       </option>
<option value="pn">Pitcairn                                          </option>
<option value="pl">Poland                                            </option>
<option value="pt">Portugal                                          </option>
<option value="pr">Puerto Rico                                       </option>
<option value="qa">Qatar                                             </option>
<option value="re">Reunion                                           </option>
<option value="ro">Romania                                           </option>
<option value="ru">Russian Federation                                </option>
<option value="rw">Rwanda                                            </option>
<option value="kn">Saint Kitts and Nevis                             </option>
<option value="lc">Saint Lucia                                       </option>
<option value="vc">Saint Vincent and the Grenadines                  </option>
<option value="ws">Samoa                                             </option>
<option value="sm">San Marino                                        </option>
<option value="st">Sao Tome and Principe                             </option>
<option value="sa">Saudi Arabia                                      </option>
<option value="sn">Senegal                                           </option>
<option value="sc">Seychelles                                        </option>
<option value="sl">Sierra Leone                                      </option>
<option value="sg">Singapore                                         </option>
<option value="sk">Slovakia (Slovak Republic)                        </option>
<option value="si">Slovenia                                          </option>
<option value="sb">Solomon Islands                                   </option>
<option value="so">Somalia                                           </option>
<option value="za">South Africa                                      </option>
<option value="gs">South Georgia and the South Sandwich Islands      </option>
<option value="es">Spain                                             </option>
<option value="lk">Sri Lanka                                         </option>
<option value="sh">St. Helena                                        </option>
<option value="pm">St. Pierre and Miquelon                           </option>
<option value="sd">Sudan                                             </option>
<option value="sr">Suriname                                          </option>
<option value="sj">Svalbard and Jan Mayen Islands                    </option>
<option value="sz">Swaziland                                         </option>
<option value="se">Sweden                                            </option>
<option value="ch">Switzerland                                       </option>
<option value="sy">Syrian Arab Republic                              </option>
<option value="tw">Taiwan                                            </option>
<option value="tj">Tajikistan                                        </option>
<option value="tz">Tanzania, United Republic of                      </option>
<option value="th">Thailand                                          </option>
<option value="tg">Togo                                              </option>
<option value="tk">Tokelau                                           </option>
<option value="to">Tonga                                             </option>
<option value="tt">Trinidad and Tobago                               </option>
<option value="tn">Tunisia                                           </option>
<option value="tr">Turkey                                            </option>
<option value="tm">Turkmenistan                                      </option>
<option value="tc">Turks and Caicos Islands                          </option>
<option value="tv">Tuvalu                                            </option>
<option value="ug">Uganda                                            </option>
<option value="ua">Ukraine                                           </option>
<option value="ae">United Arab Emirates                              </option>
<option value="gb">United Kingdom                                    </option>
<option value="us">United States                                     </option>
<option value="um">United States Minor Outlying Islands              </option>
<option value="zz">Unknown                                           </option>
<option value="uy">Uruguay                                           </option>
<option value="uz">Uzbekistan                                        </option>
<option value="vu">Vanuatu                                           </option>
<option value="va">Vatican City State (Holy See)                     </option>
<option value="ve">Venezuela                                         </option>
<option value="vn">Viet Nam                                          </option>
<option value="vg">Virgin Islands (British)                          </option>
<option value="vi">Virgin Islands (U.S.)                             </option>
<option value="wf">Wallis and Futuna Islands                         </option>
<option value="eh">Western Sahara                                    </option>
<option value="ye">Yemen                                             </option>
<option value="yu">Yugoslavia                                        </option>
<option value="zm">Zambia                                            </option>
<option value="zw">Zimbabwe                                          </option>
			</select>
		</td>
	</tr>
	<tr>
		<td><span title="Please add a short (max 100 letters) name of your model (eg : Cornet antenna radome - Brittany - France"><a style="cursor: help">Description</a></span></td>
		<td>
			<input type="text" name="mo_name" maxlength="100" size="40" value="Tell us more about your model." />
		</td>
	</tr>
	<tr>
		<td><span title="This is the WGS84 longitude of the model you want to add. Has to be between -180.000000 and +180.000000."><a style="cursor: help; ">Longitude</a></span></td>
		<td>
			<input type="text" name="longitude" maxlength="11" value="" onBlur="checkNumeric(this,-180,180,'.');" />
		</td>
	</tr>
		<tr>
		<td><span title="This is the WGS84 latitude of the model you want to add. Has to be between -90.000000 and +90.000000."><a style="cursor: help; ">Latitude</a></span></td>
		<td>
			<input type="text" name="latitude" maxlength="10" value="" onBlur="checkNumeric(this,-90,90,'.');" />
		</td>
	</tr>
	<tr>
		<td><span title="This is the ground elevation (in meters) of the position where the model you want to add is located. Warning: if your model is sunk into the ground, use the elevation offset field below."><a style="cursor: help; ">Elevation</a></span></td>
		<td>
			<input type="text" name="gndelev" maxlength="10" value="" onBlur="checkNumeric(this,-10000,10000,'.');" />
		</td>
	</tr>
	<tr>
		<td><span title="This is the offset (in meters) between your model 'zero' and the elevation at the considered place (ie if it is sunk into the ground). Let 0 if there is no offset."><a style="cursor: help; ">Elevation offset</a></span></td>
		<td>
			<input type="text" name="offset" maxlength="10" value="0" onBlur="checkNumeric(this,-10000,10000,'.');" />
		</td>
	</tr>
		<tr>
		<td><span title="The orientation (in degrees) for the object you want to add - as it appears in the STG file (this is NOT the true heading). Let 0 if there is no specific orientation."><a style="cursor: help; ">Orientation</a></span></td>
		<td>
			<input type="text" name="heading" maxlength="7" value="" onBlur="checkNumeric(this,0,359.999,'.');" />
		</td>
	</tr>
	<tr>
		<td><span title="Please add a short (max 100 letters) statement why you are inserting this data. This will help the maintainers understand what you are doing. eg: Hi, this is a new telecommunications model in Brittany, please commit"><a style="cursor: help">Comment</a></span></td>
		<td>
			<input type="text" name="comment" maxlength="100" size="40" value="Comment" />
			<input name="IPAddr" type="hidden" value="78.242.104.250" />
		</td>
	</tr>
	<tr>
		<td><span title="This is a nice picture representing your model in FG the best way."><a style="cursor: help; ">Corresponding 320x240 JPEG thumbnail</a></span></td>
		<td>
			<input type=file name="mo_thumbfile"> (i.e : tower_thumbnail.jpeg)
		</td>
	</tr>
	<tr>
		<td><span title="This is the AC3D file of your model."><a style="cursor: help; ">Corresponding AC3D File</a></span></td> 
		<td>
			<input type=file name="ac3d_file">(i.e : tower.ac)
		</td>
	</tr>
	<tr>
		<td><span title="This is the XML file of your model."><a style="cursor: help; ">Corresponding XML File</a></span></td> 
		<td>
			<input type=file name="xml_file">(i.e : tower.xml)
		</td>
	</tr>
	<tr>
		<td><span title="This(Those) is(are) the PNG texture(s) file of your model. Has to show a factor 2 between height and length."><a style="cursor: help; ">Corresponding PNG Files</a></span></td>
		<td>
			<input type="file" name="png_file[]"><br/>
			<input type="file" name="png_file[]"><br/>
			<input type="file" name="png_file[]"><br/>
			<input type="file" name="png_file[]"><br/>
			<input type="file" name="png_file[]"><br/>
			<input type="file" name="png_file[]"><br/>
			<input type="file" name="png_file[]"><br/>
			<input type="file" name="png_file[]"><br/>
			<input type="file" name="png_file[]"><br/>
			<input type="file" name="png_file[]"><br/>
			<input type="file" name="png_file[]"><br/>
			<input type="file" name="png_file[]"><br/>
		</td>
	</tr>
	<tr>
		<td colspan="2">
			<center>
			<?php
			// Google Captcha stuff
			require_once('../captcha/recaptchalib.php');
			$publickey = "6Len6skSAAAAAB1mCVkP3H8sfqqDiWbgjxOmYm_4";
			echo recaptcha_get_html($publickey);
			?>
			</center>
			<br />
			<input type="submit" value="Submit model" />
		</td>
	</tr>
</table>
</form>
</p>
</body>
</html>

