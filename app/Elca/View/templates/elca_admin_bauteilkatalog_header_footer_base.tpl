<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <style type="text/css">

        body {
            font-family: Arial,sans-serif;
            font-size: 16px;
        }
        table th, table td {
            color: #333333;
        }
        p, li, label, input, th, td, a, pre {
            color: #888;
            font-size: 12px;
        }
        .clear, .clearfix {
            clear: both;
        }

        /* reporting print header ----------------------------- */
        #content table.print-table { width: 100%; page-break-after: auto !important; }
        #content table.print-table td.print-content { padding-top: 0.5cm; }
        #content table.print-table td.print-td { border: none; }

        #content table.print-table td.print-td div.print-header, #content table.print-table td.print-td div.print-footer { display: block; padding: 0 5px; margin: 0; width: auto; text-align: center; }
        #content table.print-table td.print-td div.print-header { border: 1px solid #000; padding: 2px 4px; }
        #content table.print-table td.print-td div.print-header div.logo { padding-top: 3px; float: left; }
        #content table.print-table td.print-td div.print-header h2, #content table.print-table td.print-td div.print-header h3, #content table.print-table td.print-td div.print-header p { margin: 0; margin-left: 90px; padding: 5px 0; }

        #content table.print-table td.print-td div.print-header p.description { position: relative; }
        #content table.print-table td.print-td div.print-header p.description span { display: none; white-space: nowrap; position: absolute; top: 8px; }
        #content table.print-table td.print-td div.print-header p.description span em { font-style: normal; }
        #content table.print-table td.print-td div.print-header p.description span.left { left: 10px; }
        #content table.print-table td.print-td div.print-header p.description span.right { right: 10px; }

        body.pdf #content table.print-table td.print-td div.print-header p.description span { display: block; }

        #content table.print-table td.print-td div.print-header h3 { margin-top: 3px; }
        #content table.print-table td.print-td div.print-header h2, #content table.print-table td.print-td div.print-header h3 { border-bottom: 1px solid #000; }
        #content table.print-table td.print-td div.print-footer { margin-top: 1cm; }

        #content table.print-table div.print-footer table { width: 100%; border-collapse: collapse; border-spacing: 0; }
        #content table.print-table td.print-td div.print-footer td { border: 1px solid #000 !important; font-weight: bold; }
        #content table.print-table td.print-td div.print-footer td em { color: #666; font-weight: normal; }
        
        #elcaAdminBauteilkatalog .elcaAdminBauteilkatalogItem { page-break-before: always; font-size: 14px;}
        #elcaAdminBauteilkatalog .elcaAdminBauteilkatalogItem dl { margin: 0; padding-bottom: 10px; }
        #elcaAdminBauteilkatalog .elcaAdminBauteilkatalogItem dt { clear: both; float: left; width: 200px; padding: 0 0 10px 0; }
        #elcaAdminBauteilkatalog .elcaAdminBauteilkatalogItem dd { margin-left: 200px;  padding: 0 0 10px 0; }

         svg {
          width:800px;
          height:500px;
         }
        .button.print {
            display:none;
        }         
    </style>

    <script type="application/javascript">
      <![CDATA[
      function subst() {
          var vars = new Object();

          var x = window.location.search.substring(1).split('&');
          for (var i in x) {
              var z=x[i].split('=',2);
              vars[z[0]] = unescape(z[1]);
          }
          var what = ['topage','page'];
          for (var i in x) {
              var y = document.getElementsByClassName('wk-replace-' + what[i]);
              for (var j=0; j<y.length; ++j) {
                  y[j].textContent = vars[what[i]];
              }
          }
      }
      ]]>
    </script>
  </head>

  <body class="pdf" onload="subst()">
    <div id="outer">
        <div class="layout-width report $$action$$" id="content">
            <table class="print-table">
                <tr>
                    <td class="print-td"><include name="\Beibob\Blibs\MainContentCtrl" /></td>
                </tr>
            </table>
        </div>
    </div>
  </body>

</html>
