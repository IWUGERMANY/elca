<!DOCTYPE html>
<html lang="de">
<head>
    <title>eLCA IFC Viewer</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta http-equiv="Content-language" content="$$locale$$"/>

    <script type="text/javascript" src="/js/jquery/jquery.min.js"></script>
    <script type="text/javascript" src="/js/d3.v3/d3.v3.min.js"></script>

    <script type="text/javascript" src="/js/bimsurfer/src/threeViewer/three.js"></script>
    <script type="text/javascript" src="/js/bimsurfer/src/threeViewer/OrbitControls.js"></script>
    <script type="text/javascript" src="/js/bimsurfer/src/threeViewer/GLTFLoader.js"></script>
    <script type="text/javascript" data-main="/js/ifcViewer/ElcaIfcViewer" src="/js/bimsurfer/lib/require.js"></script>

    <link rel="shortcut icon" href="/favicon.ico"/>
</head>

<body class="$$context$$">
<div id="outer">
    <div id="viewerContent">
        <include name="\Beibob\Blibs\MainContentCtrl"/>
    </div>
</div>

<noscript>
    <div id="noscript-message">
        <p>_(eLCA IFC Viewer benötigt Javascript. Bitte aktivieren Sie Javascript in Ihrem Browser.)_</p>
    </div>
</noscript>

</body>
</html>
