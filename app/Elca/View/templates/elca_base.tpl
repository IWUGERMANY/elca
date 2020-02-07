<!DOCTYPE html>
<html>
<head>
    <title>eLCA</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="Content-language" content="$$locale$$"/>
    <meta name="description" content="_(eLCA | Online-Ökobilanztool für den Verwaltungsbau - Das Online-Bilanzierungstool eLCA dient zur Erstellung von Ökobilanzen für Gebäude auf Basis von Bauteilvorlagen. Gegenstand des Forschungsprojektes war die Erarbeitung von Bauteilvorlagen und Gebäudeökobilanzen mittels des Onlinetools.)_"/>
    <meta name="keywords" content="_(Ökobilanz, Verwaltungsbau, Ökobilanzdaten, LCA, Life Cycle Assessment, DIN 276, BMVBS, BBSR, Baustoffdatenbank Ökobau.dat, Ökobau.dat, Baustoffdatenbank, Forschungsinitiative Zukunft Bau, Bundesinstitut für Bau- Stadt- und Raumforschung)_"/>
    <meta name="robots" content="index, follow"/>

    <!--<link rel="stylesheet" type="text/css" href="/css/elca/elca.css" media="all"/>
    <link rel="stylesheet" type="text/css" href="/css/elca/print.css" media="all"/> -->

    <script type="text/javascript" src="/js/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="/js/d3.v3/d3.v3.min.js"></script>
    <!-- <script type="text/javascript" src="/js/elca/elca.min.js"></script> -->

    <link rel="shortcut icon" href="/favicon.ico"/>
</head>

<body class="$$context$$ $$pdfmode$$ $$highlightMissingTranslations$$">
<div id="outer">
    <div id="header">
        <div id="bmub"><img src="/img/elca/elca_logo.png" width="73" height="73" alt=""/></div>
        <div class="layout-width">
            <h1 class="logo"><a href="/" class="no-xhr">eLCA</a> <a href="/index/versions/" class="no-xhr"><span class="elca-version">$$version$$</span> <span class="beta">beta</span></a></h1>
            <include name="\Elca\View\ElcaNavigationMetaView"/>
        </div>
    </div>

    <include name="\Elca\View\ElcaNavigationTopView"/>
    <div id="inner">
        <include name="\Elca\View\ElcaContentHeadView" Project="$$Project$$"/>

        <div class="main-content $$context$$">
            <div id="navLeft"></div>
            <div id="main">
                <include name="\Elca\View\ElcaOsitView"/>
                <div id="content">
                    <include name="\Beibob\Blibs\MainContentCtrl"/>
                </div>
            </div>
        </div>
    </div>
    <div id="footer"></div>
</div>

<!-- message info box -->
<include name="\Elca\View\ElcaMessagesView"/>
<include name="\Elca\View\ElcaModalBoxView"/>

<div id="loading"></div>
<p id="progress"></p>

<noscript>
    <div id="noscript-message">
        <p>_(eLCA benötigt Javascript. Bitte aktivieren Sie Javascript in Ihrem Browser.)_</p>
    </div>
</noscript>

<span id="diagramsLoading">_(Diagramme werden geladen)_</span>
</body>

</html>
