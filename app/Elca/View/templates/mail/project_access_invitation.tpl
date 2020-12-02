<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style type="text/css">
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
        table {

        }
        .mail-header,
        .mail-content,
        .mail-footer {
            width: 600px;
        }
        .mail-header {
            background-color: #004f80;
        }
        .mail-header h3 {
            padding: 0 20px;
            font-size: 18px;
            font-style: italic;
            font-weight: normal;
            color: #dbdbdb;
        }
        .mail-header .version {
            font-size:12px;
        }

        .mail-content {
            color: #666;
            line-height:1.3;
        }
        .mail-content a {
            color:#336699;
        }


        .mail-footer {
            margin-top:20px;
            border-top: 1px solid #f0f0f0;
            padding-top:20px;
        }
        .mail-footer address {
            font-style: normal;
        }
        .mail-footer address, .mail-footer a  {
            color:#666;
        }


    </style>
</head>

<body>

<div class="mail-header">
    <table><tr><td><img class="elca-logo" src="$$imageBaseUrl$$elca_logo.png"/></td><td><h3>eLCA <span class="version">$$version$$</span></h3></td></tr> </table>
</div>

<div class="mail-content">
    <p>_(Hallo)_,</p>
    <p>_(Sie sind von )_ <strong>$$senderName$$</strong> _(eingeladen worden, an dem Projekt)_ <strong>$$projectName$$</strong> _(mitzuwirken.)_
        _(Wenn Sie die Einladung annehmen möchten, dann öffnen Sie bitte den folgenden Link in Ihrem Browser:)_</p>

    <p><a href="$$invitationUrl$$">$$invitationUrl$$</a></p>

    <p>_(Diese Einladung setzt voraus, dass Sie bereits im Besitz von Zugangsdaten für eLCA sind. Sollten Sie noch keine Anmeldedaten
        erhalten haben oder Probleme mit der Anmeldung haben, wenden Sie sich bitte an)_ <a class="no-xhr" href="mailto:anmeldung@bauteileditor.de">anmeldung@bauteileditor.de</a>_(.
        Sobald Ihnen die Anmeldedaten für eLCA vorliegen, folgen Sie bitte dem oben aufgeführten Link.)_
    </p>
    <p>_(Freundliche Grüße,<br/>
        Ihr eLCA Team)_</p>
</div>

<div class="mail-footer">
    <address>
        _(Bundesinstitut für Bau-, Stadt- und Raumforschung (BBSR)<br/>
        im Bundesamt für Bauwesen und Raumordnung (BBR)<br/>
        Referat II 6 Bauen und Umwelt<br/>
        <br/>
        Postanschrift Straße des 17. Juni 112, 10623 Berlin<br/>
        Hausanschrift Reichpietschufer 86-90, 10785 Berlin<br/>
        <br/>
        Tel. +49(0)30 18-401-3417<br/>
        Fax. +49(0)30 18-401-2769)_
        <br/><br/>
        <a href="$$siteUrl$$">$$siteUrl$$</a>
    </address>
</div>
</body>
</html>