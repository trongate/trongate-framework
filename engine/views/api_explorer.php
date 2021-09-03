<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>API Explorer</title>
    <meta name="description" content="">
    <meta name="author" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="//fonts.googleapis.com/css?family=Raleway:400,300,600" rel="stylesheet" type="text/css">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
</head>
<body>
<script>
function clearContent(){document.getElementById("params").value="",setTimeout(function(){document.getElementById("clearParams").checked=!1},600)}var golden_token="<?= $golden_token ?>",token="";function setToken(){token=document.getElementById("input-token").value,document.getElementById("token-value").innerHTML=token,document.getElementById("token-status").innerHTML=""==token?"Token Not Set!":"Token is set."}var url_segments,endpoint,requestType="GET",extraFieldsHtml="",extraRequiredFields=[];function expiredGoldenToken(){document.getElementById("stage").innerHTML="";var e='<h4 style="text-align:center;">Login Expired</h4>';e=e.concat('<p style="text-align:center;">Your session has expired.  Please login again.</p>'),document.getElementById("stage").innerHTML=e,document.getElementById("top-block").innerHTML='<div class="w3-col s5 w3-center logo">Trongate API Explorer</div>',document.getElementById("top-block").style.minHeight="6.66em",document.getElementById("id01").style.display="none"}var isObj=function(e){return!(!e||e.constructor!==Object)},_st=function(e,t){return(""!=t?"[":"")+e+(""!=t?"]":"")},fromObject=function(e,t,n){void 0===t&&(t=!1),void 0===n&&(n="");var o="";if("object"!=typeof e)return n+"="+encodeURIComponent(e)+"&";for(var r in e){var i=""+n+_st(r,n);isObj(e[r])&&!t?o+=fromObject(e[r],!1,""+i):Array.isArray(e[r])&&!t?e[r].forEach(function(e,t){o+=fromObject(e,!1,i+"["+t+"]")}):o+=i+"="+encodeURIComponent(e[r])+"&"}return o=o.slice(0,-1)};function replacePlaceholders(e){for(var t=0;t<extraRequiredFields.length;t++){var n="extra-required-field-"+extraRequiredFields[t].name,o=document.getElementById(n).value;if(""==(o=o.replace(/ /g,"")))return alert("You did not enter a value for "+extraRequiredFields[t].name),document.getElementById(n).value="",!1;var r="{"+extraRequiredFields[t].name+"}",i=o;e=e.replace(r,i)}return e}var HTTP_STATUS_CODES={CODE_200:"OK",CODE_201:"Created",CODE_202:"Accepted",CODE_203:"Non-Authoritative Information",CODE_204:"No Content",CODE_205:"Reset Content",CODE_206:"Partial Content",CODE_300:"Multiple Choices",CODE_301:"Moved Permanently",CODE_302:"Found",CODE_303:"See Other",CODE_304:"Not Modified",CODE_305:"Use Proxy",CODE_307:"Temporary Redirect",CODE_400:"Bad Request",CODE_401:"Unauthorized",CODE_402:"Payment Required",CODE_403:"Forbidden",CODE_404:"Not Found",CODE_405:"Method Not Allowed",CODE_406:"Not Acceptable",CODE_407:"Proxy Authentication Required",CODE_408:"Request Timeout",CODE_409:"Conflict",CODE_410:"Gone",CODE_411:"Length Required",CODE_412:"Precondition Failed",CODE_413:"Request Entity Too Large",CODE_414:"Request-URI Too Long",CODE_415:"Unsupported Media Type",CODE_416:"Requested Range Not Satisfiable",CODE_422:"Unprocessable Entity",CODE_417:"Expectation Failed",CODE_500:"Internal Server Error",CODE_501:"Not Implemented",CODE_502:"Bad Gateway",CODE_503:"Service Unavailable",CODE_504:"Gateway Timeout",CODE_505:"HTTP Version Not Supported"};
</script>
<div class="top-row w3-row">
    <div class="container" id="top-block">
        <div class="w3-col s5 w3-center logo">Trongate API Explorer</div>
        <div class="w3-col s2 w3-center trhs"><p id="token-status">Token Not Set!</p></div>
        <div class="w3-col s3 w3-center trhs">
            <p>
                <input id="input-token" class="w3-input w3-border" type="text" placeholder="Enter Authorization Token">
            </p>
        </div>
        <div class="w3-col s2 w3-center trhs">
            <p>
                <button onclick="setToken()" class="w3-button button-primary default set-token-btn">Set Token</button>
            </p>
        </div>
    </div>
</div>

<div>
    <div class="container" id="stage" style="margin-top: 5em;">
        <div class="row">
            <div>
                <h4><?= $target_module ?></h4>
                <table class="u-full-width" style="font-size: 1.4em;">
                    <thead>
                        <tr>
                            <th class="go-left">Request Type</th>
                            <th class="go-left">Endpoint Name</th>
                            <th class="go-left">URL segments</th>
                            <th class="go-right">Description</th>
                        </tr>
                    </thead>
                <tbody>
                <?php
                foreach($endpoints as $endpoint_name => $endpoint) {
                    $endpoint_json = json_encode($endpoint);

                        switch($endpoint['request_type']) {
                            case 'GET':
                                $btn_theme = 'green';
                                break;
                            case 'POST':
                                $btn_theme = 'purple';
                                break;
                            case 'PUT':
                                $btn_theme = 'deep-purple';
                                break;
                            case 'DELETE':
                                $btn_theme = 'red';
                                break;
                            default:
                                $btn_theme = 'green';
                                break;
                        }

                        $endpoint_data = json_encode($endpoint);
                        $ditch = '"';
                        $replace = '&quot;';
                        $endpoint_data = str_replace($ditch, $replace, $endpoint_data);

                        $ditch = '}';
                        $replace = '<span class="alt-font">}</span>';
                        $endpoint['url_segments'] = str_replace($ditch, $replace, $endpoint['url_segments']);
                        $ditch = '{';
                        $replace = '<span class="alt-font">{</span>';
                        $endpoint['url_segments'] = str_replace($ditch, $replace, $endpoint['url_segments']);
                    ?>
                    <tr>
                        <td style="font-size: 0.8em;"><input onclick="openModal('<?= $endpoint_name ?>', '<?= $endpoint_data ?>')" type="submit" value="<?= $endpoint['request_type'] ?>" class="button-primary <?= $btn_theme ?>"></td>
                        <td><?= $endpoint_name ?></td>
                        <td><?= $endpoint['url_segments'] ?></td>
                        <td class="go-right"><?= $endpoint['description'] ?></td>
                    </tr>
                    <?php 
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div id="id01" class="w3-modal">
    <div class="w3-modal-content w3-animate-zoom w3-card-4">
        <header id="modal-header" class="w3-container theme-b white-text">
            <span onclick="document.getElementById('id01').style.display='none'" class="w3-button w3-display-topright">&times;</span>
            <h2 id="endpointName"></h2>
        </header>
        <div id="modal-content" class="w3-container modal-content-get">
            <h4 style="margin-top: 0.4em;">Test Your API Endpoint</h4>
            <form>
                <div class="row">
                    <div class="six columns">
                        <div class="twelve columns">
                            <div id="extra-required-fields"></div>
                            <label for="exampleMessage">Parameters</label>
                            <textarea onclick="prePopulate()" onkeydown="if(event.keyCode===9){var v=this.value,s=this.selectionStart,e=this.selectionEnd;this.value=v.substring(0, s)+'   '+v.substring(e);this.selectionStart=this.selectionEnd=s+3;return false;}" class="u-full-width" placeholder="Enter parameters in JSON format" id="params"></textarea>
                            <div class="w3-row">
                                <div class="w3-col s6">
                                    Bypass authorization <input onclick="initBypassAuth()" type="checkbox" id="bypass" value="1">
                                </div>
                                <div class="w3-col s6 w3-center" id="clearParamsBox">
                                    Clear Parameters <input onclick="clearContent()" type="checkbox" id="clearParams" value="1">
                                </div>
                            </div>
                            <input onclick="submitRequest()" class="button-primary" type="button" value="Submit"> 
                        </div>
                    </div>
                    <div class="six columns">
                        <label for="exampleMessage">Server Response <span id="http-status-code"></span></label>
                        <textarea disabled class="u-full-width server-response" id="serverResponse"></textarea>
                        <label class="example-send-yourself-copy">
                            <input onclick="displayHeaders()" type="checkbox" id="display-headers-checkbox" value="1">
                            <span class="label-body">Display Response Header Values</span>
                        </label>
                        <p id="header-info"></p>
                        <p class="go-right">
                            <input onclick="copyText()" class="button-default white-btn go-right" type="button" value="Copy Response BODY">
                            <input onclick="viewSettings()" class="button-default white-btn go-right" type="button" value="View Settings">
                        </p>
                    </div>
                </div>
            </form>
            <p style="font-size: 0.9em;"><b>URL Segments:</b> /<span id="endpointUrl"></span>
                <br>
                <b>Required HTTP Request Type: </b> <span id="requestType"></span>
                <br>
                <b>Endpoint Settings: </b>
                <?= $endpoint_settings_location ?><br>
                <b>Your Current Token: </b><span id="token-value"></span>
            </p>
        </div>
        <footer id="modal-footer">
            <p style="text-align: center;">For full documentation and tutorials visit <a href="https://trongate.io/" target="_blank">Trongate.io</a></p>
        </footer>
    </div>
</div>

<style>
/*
* Skeleton V2.0.4
* Copyright 2014, Dave Gamache
* www.getskeleton.com
* Free to use under the MIT license.
* http://www.opensource.org/licenses/mit-license.php
* 12/29/2014
*/


/* Table of contents
––––––––––––––––––––––––––––––––––––––––––––––––––
- Grid
- Base Styles
- Typography
- Links
- Buttons
- Forms
- Lists
- Code
- Tables
- Spacing
- Utilities
- Clearing
- Media Queries
*/


/* Grid
–––––––––––––––––––––––––––––––––––––––––––––––––– */
.container {
  position: relative;
  width: 100%;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 20px;
  box-sizing: border-box; }
.column,
.columns {
  width: 100%;
  float: left;
  box-sizing: border-box; }

/* For devices larger than 400px */
@media (min-width: 400px) {
  .container {
    width: 85%;
    padding: 0; }
}

/* For devices larger than 550px */
@media (min-width: 550px) {
  .container {
    width: 80%; }
  .column,
  .columns {
    margin-left: 4%; }
  .column:first-child,
  .columns:first-child {
    margin-left: 0; }

  .one.column,
  .one.columns                    { width: 4.66666666667%; }
  .two.columns                    { width: 13.3333333333%; }
  .three.columns                  { width: 22%;            }
  .four.columns                   { width: 30.6666666667%; }
  .five.columns                   { width: 39.3333333333%; }
  .six.columns                    { width: 48%;            }
  .seven.columns                  { width: 56.6666666667%; }
  .eight.columns                  { width: 65.3333333333%; }
  .nine.columns                   { width: 74.0%;          }
  .ten.columns                    { width: 82.6666666667%; }
  .eleven.columns                 { width: 91.3333333333%; }
  .twelve.columns                 { width: 100%; margin-left: 0; }

  .one-third.column               { width: 30.6666666667%; }
  .two-thirds.column              { width: 65.3333333333%; }

  .one-half.column                { width: 48%; }

  /* Offsets */
  .offset-by-one.column,
  .offset-by-one.columns          { margin-left: 8.66666666667%; }
  .offset-by-two.column,
  .offset-by-two.columns          { margin-left: 17.3333333333%; }
  .offset-by-three.column,
  .offset-by-three.columns        { margin-left: 26%;            }
  .offset-by-four.column,
  .offset-by-four.columns         { margin-left: 34.6666666667%; }
  .offset-by-five.column,
  .offset-by-five.columns         { margin-left: 43.3333333333%; }
  .offset-by-six.column,
  .offset-by-six.columns          { margin-left: 52%;            }
  .offset-by-seven.column,
  .offset-by-seven.columns        { margin-left: 60.6666666667%; }
  .offset-by-eight.column,
  .offset-by-eight.columns        { margin-left: 69.3333333333%; }
  .offset-by-nine.column,
  .offset-by-nine.columns         { margin-left: 78.0%;          }
  .offset-by-ten.column,
  .offset-by-ten.columns          { margin-left: 86.6666666667%; }
  .offset-by-eleven.column,
  .offset-by-eleven.columns       { margin-left: 95.3333333333%; }

  .offset-by-one-third.column,
  .offset-by-one-third.columns    { margin-left: 34.6666666667%; }
  .offset-by-two-thirds.column,
  .offset-by-two-thirds.columns   { margin-left: 69.3333333333%; }

  .offset-by-one-half.column,
  .offset-by-one-half.columns     { margin-left: 52%; }

}


/* Base Styles
–––––––––––––––––––––––––––––––––––––––––––––––––– */
/* NOTE
html is set to 62.5% so that all the REM measurements throughout Skeleton
are based on 10px sizing. So basically 1.5rem = 15px :) */
html {
  font-size: 62.5%; }
body {
  font-size: 1.5em; /* currently ems cause chrome bug misinterpreting rems on body element */
  line-height: 1.6;
  font-weight: 400;
  font-family: "Raleway", "HelveticaNeue", "Helvetica Neue", Helvetica, Arial, sans-serif;
  color: #222; }


/* Typography
–––––––––––––––––––––––––––––––––––––––––––––––––– */
h1, h2, h3, h4, h5, h6 {
  margin-top: 0;
  margin-bottom: 2rem;
  font-weight: 300; }
h1 { font-size: 4.0rem; line-height: 1.2;  letter-spacing: -.1rem;}
h2 { font-size: 3.6rem; line-height: 1.25; letter-spacing: -.1rem; }
h3 { font-size: 3.0rem; line-height: 1.3;  letter-spacing: -.1rem; }
h4 { font-size: 2.4rem; line-height: 1.35; letter-spacing: -.08rem; }
h5 { font-size: 1.8rem; line-height: 1.5;  letter-spacing: -.05rem; }
h6 { font-size: 1.5rem; line-height: 1.6;  letter-spacing: 0; }

/* Larger than phablet */
@media (min-width: 550px) {
  h1 { font-size: 5.0rem; }
  h2 { font-size: 4.2rem; }
  h3 { font-size: 3.6rem; }
  h4 { font-size: 3.0rem; }
  h5 { font-size: 2.4rem; }
  h6 { font-size: 1.5rem; }
}

p {
  margin-top: 0; }


/* Links
–––––––––––––––––––––––––––––––––––––––––––––––––– */
a {
  color: #1EAEDB; }
a:hover {
  color: #0FA0CE; }


/* Buttons
–––––––––––––––––––––––––––––––––––––––––––––––––– */
.button,
button,
input[type="submit"],
input[type="reset"],
input[type="button"] {
  display: inline-block;
  height: 38px;
  padding: 0 30px;
  color: #555;
  text-align: center;
  font-size: 11px;
  font-weight: 600;
  line-height: 38px;
  letter-spacing: .1rem;
  text-transform: uppercase;
  text-decoration: none;
  white-space: nowrap;
  background-color: transparent;
  border-radius: 4px;
  border: 1px solid #bbb;
  cursor: pointer;
  box-sizing: border-box; }
.button:hover,
button:hover,
input[type="submit"]:hover,
input[type="reset"]:hover,
input[type="button"]:hover,
.button:focus,
button:focus,
input[type="submit"]:focus,
input[type="reset"]:focus,
input[type="button"]:focus {
  color: #333;
  border-color: #888;
  outline: 0; }
.button.button-primary,
button.button-primary,
input[type="submit"].button-primary,
input[type="reset"].button-primary,
input[type="button"].button-primary {
  color: #FFF;
  background-color: #33C3F0;
  border-color: #33C3F0; }
.button.button-primary:hover,
button.button-primary:hover,
input[type="submit"].button-primary:hover,
input[type="reset"].button-primary:hover,
input[type="button"].button-primary:hover,
.button.button-primary:focus,
button.button-primary:focus,
input[type="submit"].button-primary:focus,
input[type="reset"].button-primary:focus,
input[type="button"].button-primary:focus {
  color: #FFF;
  background-color: #1EAEDB;
  border-color: #1EAEDB; }


/* Forms
–––––––––––––––––––––––––––––––––––––––––––––––––– */
input[type="email"],
input[type="number"],
input[type="search"],
input[type="text"],
input[type="tel"],
input[type="url"],
input[type="password"],
textarea,
select {
  height: 38px;
  padding: 6px 10px; /* The 6px vertically centers text on FF, ignored by Webkit */
  background-color: #fff;
  border: 1px solid #D1D1D1;
  border-radius: 4px;
  box-shadow: none;
  box-sizing: border-box; }
/* Removes awkward default styles on some inputs for iOS */
input[type="email"],
input[type="number"],
input[type="search"],
input[type="text"],
input[type="tel"],
input[type="url"],
input[type="password"],
textarea {
  -webkit-appearance: none;
     -moz-appearance: none;
          appearance: none; }
textarea {
  min-height: 65px;
  padding-top: 6px;
  padding-bottom: 6px; }
input[type="email"]:focus,
input[type="number"]:focus,
input[type="search"]:focus,
input[type="text"]:focus,
input[type="tel"]:focus,
input[type="url"]:focus,
input[type="password"]:focus,
textarea:focus,
select:focus {
  border: 1px solid #33C3F0;
  outline: 0; }
label,
legend {
  display: block;
  margin-bottom: .5rem;
  font-weight: 600; }
fieldset {
  padding: 0;
  border-width: 0; }
input[type="checkbox"],
input[type="radio"] {
  display: inline; }
label > .label-body {
  display: inline-block;
  margin-left: .5rem;
  font-weight: normal; }


/* Lists
–––––––––––––––––––––––––––––––––––––––––––––––––– */
ul {
  list-style: circle inside; }
ol {
  list-style: decimal inside; }
ol, ul {
  padding-left: 0;
  margin-top: 0; }
ul ul,
ul ol,
ol ol,
ol ul {
  margin: 1.5rem 0 1.5rem 3rem;
  font-size: 90%; }
li {
  margin-bottom: 1rem; }


/* Code
–––––––––––––––––––––––––––––––––––––––––––––––––– */
code {
  padding: .2rem .5rem;
  margin: 0 .2rem;
  font-size: 90%;
  white-space: nowrap;
  background: #F1F1F1;
  border: 1px solid #E1E1E1;
  border-radius: 4px; }
pre > code {
  display: block;
  padding: 1rem 1.5rem;
  white-space: pre; }


/* Tables
–––––––––––––––––––––––––––––––––––––––––––––––––– */
th,
td {
  padding: 12px 15px;
  text-align: left;
  border-bottom: 1px solid #E1E1E1; }
th:first-child,
td:first-child {
  padding-left: 0; }
th:last-child,
td:last-child {
  padding-right: 0; }


/* Spacing
–––––––––––––––––––––––––––––––––––––––––––––––––– */
button,
.button {
  margin-bottom: 1rem; }
input,
textarea,
select,
fieldset {
  margin-bottom: 1.5rem; }
pre,
blockquote,
dl,
figure,
table,
p,
ul,
ol,
form {
  margin-bottom: 2.5rem; }


/* Utilities
–––––––––––––––––––––––––––––––––––––––––––––––––– */
.u-full-width {
  width: 100%;
  box-sizing: border-box; }
.u-max-full-width {
  max-width: 100%;
  box-sizing: border-box; }
.u-pull-right {
  float: right; }
.u-pull-left {
  float: left; }


/* Misc
–––––––––––––––––––––––––––––––––––––––––––––––––– */
hr {
  margin-top: 3rem;
  margin-bottom: 3.5rem;
  border-width: 0;
  border-top: 1px solid #E1E1E1; }


/* Clearing
–––––––––––––––––––––––––––––––––––––––––––––––––– */

/* Self Clearing Goodness */
.container:after,
.row:after,
.u-cf {
  content: "";
  display: table;
  clear: both; }


/* Media Queries
–––––––––––––––––––––––––––––––––––––––––––––––––– */
/*
Note: The best way to structure the use of media queries is to create the queries
near the relevant code. For example, if you wanted to change the styles for buttons
on small devices, paste the mobile query code up in the buttons section and style it
there.
*/


/* Larger than mobile */
@media (min-width: 400px) {}

/* Larger than phablet (also point when grid becomes active) */
@media (min-width: 550px) {}

/* Larger than tablet */
@media (min-width: 750px) {}

/* Larger than desktop */
@media (min-width: 1000px) {}

/* Larger than Desktop HD */
@media (min-width: 1200px) {}

.header{background:#50459b}.go-left{text-align:left;position:relative}.go-right{text-align:right;position:relative}h4{font-weight:700}.default:hover{background:#22abd6!important;color:#fff!important}.trhs{top:.8em;position:relative}.logo{font-size:1.6em;margin:0!important;padding:0!important;font-weight:700}.top-row{background:#50459b;color:#eee;min-height:5em;line-height:5em}.top-row .w3-button{width:96%}.top-row>.container{display:flex;flex-direction:row;justify-content:space-between;align-items:center}.w3-quarter{min-height:6em;background:orange;display:flex;flex-direction:column;justify-content:center}.button.button-primary,button.button-primary,input[type=button].button-primary,input[type=reset].button-primary,input[type=submit].button-primary{padding:0 1em;min-width:94px;font-size:.9em;margin:0;padding:0}.purple{background-color:#bb9fe0!important;border:1px #bb9fe0 solid!important}.purple:hover{background-color:#a791c4!important;border:1px #a791c4 solid!important}.deep-purple{background-color:#470b59!important;border:1px #470b59 solid!important}.deep-purple:hover{background-color:#3b0a49!important;border:1px #3b0a49 solid!important}.green{background-color:#0285a1!important;border:1px #0285a1 solid!important}.green:hover{background-color:#02738c!important;border:1px #02738c solid!important}.red{background-color:#a11e02!important;border:1px #a11e02 solid!important}.red:hover{background-color:#6f1501!important;border:1px #6f1501 solid!important}td{padding:8px;vertical-align:center!important}.star{font-size:1.4em}.generate-btn{min-width:180px!important;margin-left:1em!important}.white-btn{background-color:#fff!important}.alt-font{font-family:"Lucida Console",Monaco,monospace}.w3-display-topright{font-size:2em;padding:0 .6em}#http-status-code{color:green}#header-info p{margin:0}footer a:link{color:#fff}footer a:active{color:#fff}footer a:visited{color:#fff}footer a:hover{color:#fff}textarea{min-height:200px;font-family:"Lucida Console",Monaco,monospace}.server-response{background:#fcfbe3}.modal-content{background:#f3f2ff}.modal-content-get{background:#eaf9fc}.modal-content-put{background:#f7eff9}.modal-content-delete{background:#fff}.theme-a p,.theme-b p,.theme-c p,.theme-d p{padding:1em 0;margin:0}.w3-modal h2{padding:.2em 0;margin:0;font-size:2em}.theme-a{background-color:#50459b}.theme-b{background-color:#0285a1}.theme-c{background-color:#470b59}.theme-d{background-color:#a11e02}.white-text{color:#fff}
    .set-token-btn {border: 1px #33C3F0 solid; top:-1px !important; position: relative;}
</style>
<script>
function generateNewGoldenToken(e){var t=new Date;t.setHours(t.getHours()+4),expiryDate=Date.parse(t)/1e3;const n=new XMLHttpRequest;n.open("POST","<?= BASE_URL ?>trongate_tokens/regenerate/"+e+"/"+expiryDate),n.setRequestHeader("Content-type","application/json"),n.send(JSON.stringify(params)),n.onload=function(){"false"==n.responseText?expiredGoldenToken():golden_token=n.responseText}}function submitRequest(){var e=document.getElementById("params").value;document.getElementById("endpointUrl").innerHTML=initialSegments,setTimeout(function(){document.getElementById("bypass").checked=!1,token==golden_token&&(token="",generateNewGoldenToken(golden_token))},600);var t="<?= BASE_URL ?>"+document.getElementById("endpointUrl").innerHTML;if(""!=e&&(isValidJson=validateJson(e),0==isValidJson))return void alert("Invalid JSON");if("GET"==requestType&&""!=e){e=(e=(e=(e=e.replace(/>/g,"*!gt!*")).replace(/</g,"*!lt!*")).replace(/=/g,"*!equalto!*")).replace(/_/g,"*!underscore!*"),e=JSON.parse(e);var n="/?"+fromObject(e);n=n.replace(/ /gi,"%20"),t=t.concat(n)}if(t=t.replace(/<(.|\n)*?>/g,""),0==(t=replacePlaceholders(t)))return;const d=new XMLHttpRequest;d.open(requestType,t),d.setRequestHeader("Content-type","application/json"),d.setRequestHeader("trongateToken",token),d.send(e),d.onload=function(){document.getElementById("endpointUrl").innerHTML=t.replace("<?= BASE_URL ?>",""),responseHeaders=d.getAllResponseHeaders(),responseHeaders=responseHeaders.replace(/(?:\r\n|\r|\n)/g,"<br>"),headerInfo='<p style="font-weight: bold;">HTTP Header Values </p><span style="font-size: 0.8em;">'+responseHeaders+"</span>",1==document.getElementById("display-headers-checkbox").checked&&(document.getElementById("header-info").innerHTML=headerInfo),200==d.status?document.getElementById("http-status-code").style.color="green":document.getElementById("http-status-code").style.color="purple",document.getElementById("http-status-code").innerHTML=d.status+" "+HTTP_STATUS_CODES["CODE_"+d.status],document.getElementById("serverResponse").disabled=!1,document.getElementById("serverResponse").value=d.responseText}}function drawRequiredFields(e){extraFieldsHtml="";for(var t=0;t<e.length;t++){var n=e[t].name,d=e[t].label;extraFieldsHtml=extraFieldsHtml.concat('<label for="text_field">'+d+"</label>"),extraFieldsHtml=extraFieldsHtml.concat('<input type="text" name="'+n+'" id="extra-required-field-'+n+'" class="u-full-width" placeholder="Enter '+d+' here">');var a={name:n,label:d};extraRequiredFields.push(a)}document.getElementById("extra-required-fields").innerHTML=extraFieldsHtml}var endpoint_settings="",initialSegments="";function openModal(e,t){t = t.replace(/{\$id}/g, '{id}');document.getElementById("serverResponse").value="",document.getElementById("http-status-code").innerHTML="",document.getElementById("header-info").innerHTML="",document.getElementById("extra-required-fields").innerHTML="",document.getElementById("bypass").checked=!1,extraRequiredFields=[],endpoint_data=JSON.parse(t),endpoint_settings=t,1==endpoint_data.enableParams?(document.getElementById("params").disabled=!1,document.getElementById("params").style.backgroundColor="#fff",document.getElementById("params").style.minHeight="200px",document.getElementById("clearParamsBox").style.display="inline"):(document.getElementById("params").value="",document.getElementById("params").disabled=!0,document.getElementById("params").style.backgroundColor="#ddd",document.getElementById("params").style.minHeight="118px",document.getElementById("clearParamsBox").style.display="none"),endpoint_data.required_fields&&setTimeout(drawRequiredFields,100,endpoint_data.required_fields),url_segments=endpoint_data.url_segments.replace(/{/g,'<span class="alt-font">{</span>'),url_segments=url_segments.replace(/}/g,'<span class="alt-font">}</span>'),initialSegments=url_segments,requestType=endpoint_data.request_type;endpoint_data.description;"POST"==requestType&&(document.getElementById("modal-header").className="w3-container theme-a white-text",document.getElementById("modal-footer").className="w3-container theme-a white-text",document.getElementById("modal-content").className="w3-container modal-content"),"GET"==requestType&&(document.getElementById("modal-header").className="w3-container theme-b white-text",document.getElementById("modal-footer").className="w3-container theme-b white-text",document.getElementById("modal-content").className="w3-container modal-content-get"),"PUT"==requestType&&(document.getElementById("modal-header").className="w3-container theme-c white-text",document.getElementById("modal-footer").className="w3-container theme-c white-text",document.getElementById("modal-content").className="w3-container modal-content-put"),"DELETE"==requestType&&(document.getElementById("modal-header").className="w3-container theme-d white-text",document.getElementById("modal-footer").className="w3-container theme-d white-text",document.getElementById("modal-content").className="w3-container modal-content-delete"),document.getElementById("id01").style.display="block",document.getElementById("endpointName").innerHTML=e,document.getElementById("requestType").innerHTML=requestType,document.getElementById("endpointUrl").innerHTML=url_segments}function viewSettings(){alert(endpoint_settings)}function copyText(){var e=document.getElementById("serverResponse");e.select(),document.execCommand("copy"),alert("Copied the text: "+e.value)}var modal=document.getElementById("id01");window.onclick=function(e){e.target==modal&&(modal.style.display="none")};var headerInfo="";function initBypassAuth(){if(isChecked=document.getElementById("bypass").checked,1==isChecked){token=golden_token;var e=new Date;e.setHours(e.getHours()+4),expiryDate=Date.parse(e)/1e3;const t=new XMLHttpRequest;t.open("POST","<?= BASE_URL ?>trongate_tokens/regenerate/"+token+"/"+expiryDate),t.setRequestHeader("Content-type","application/json"),t.send(JSON.stringify(params)),t.onload=function(){"false"==t.responseText?expiredGoldenToken():(token=t.responseText,golden_token=token,document.getElementById("input-token").value=token,document.getElementById("token-value").innerHTML=token)}}}function displayHeaders(){isChecked=document.getElementById("display-headers-checkbox").checked,1==isChecked?document.getElementById("header-info").innerHTML=headerInfo:document.getElementById("header-info").innerHTML=""}function validateJson(e){return!!function(e){try{return"string"==typeof e&&(JSON.parse(e),!0)}catch(e){return!1}}(e)}

function prePopulate() {

    var thisEndpointName = document.getElementById("endpointName").innerHTML;

    if ((thisEndpointName == 'Create') || (thisEndpointName == 'Update')) {

        if ((document.getElementById("params").value == '')) {
            document.getElementById("params").value = '<?= $json_starter_str ?>';
            var txtElement = document.getElementById("params");
            resetCursor(txtElement);
        }

    }

}

function resetCursor(txtElement) { 
    txtElement.focus(); 
    txtElement.setSelectionRange(<?= $cursor_reset_position ?>, <?= $cursor_reset_position ?>); 
}
</script>
</body>
</html>