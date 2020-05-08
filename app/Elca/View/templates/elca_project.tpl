<div id="content" class="elca-project">
    <h1>_(Projektstartseite)_</h1>

    <div id="windowAssistantProblem" class="elca-project-sanity">
        <h3>Hinweis zu einem möglichen Problem mit dem Fensterassistenten</h3>
        <p>In Ihrem Projekt kann es zu Problemen mit den Bauteilkomponenten gekommen sein, die mithilfe des Fenster-
            Assistenten erstellt wurden. Folgende Probleme sind hierbei möglich:</p>

        <ul class="list">
            <li>Aufgrund eines Fehlers sind möglicherweise nicht alle Bauteilkomponenten, die einem Fenster zugeordnet
                werden können (z.B. Laibung, Fensterbänke, Sonnenschutz),
                berechnet worden. Sie können das Problem lösen, indem Sie das Projekt neu berechnen
            </li>
            <li>Falls Sie Projektvarianten angelegt haben, prüfen Sie bitte in diesen Varianten, ob die
                Bauteilkomponenten eines Fensters (z.B. Laibung, Fensterbänke, Sonnenschutz) korrekt kopiert wurden.
            </li>
        </ul>

        <p>Wir entschuldigen uns für Ihre hierdurch entstanden Umstände.</p>
    </div>

    <include name="Elca\View\ElcaProjectProcessConfigSanityView" readOnly="$$readOnly$$"/>



    <div id="cacheWarning" class="elca-project-sanity">
        <h3>Fehler im Ergebnis-Zwischenspeicher</h3>
        <p>
            Aufgrund eines Programmfehlers kam es in einigen Projekten zu Problemen mit der Zwischenspeicherung
            von Ergebniswerten. Das Problem wurde in der Zwischenzeit behoben.
        </p>

        <p>
            <u>Eine Analyse hat ergeben, dass dieses Projekt davon betroffen war.</u><br/><br/>
            Sie können die Projektergebnisse jetzt neu berechnen. Dabei werden die Ergebnisse <u>sämtlicher</u>
            Projektvarianten
            verworfen und neu berechnet! Falls Sie die aktuellen Ergebnisse jedoch erhalten möchten, empfehlen wir Ihnen
            das vorliegende Projekt zu kopieren oder die Auswertungen als PDFs zu sichern, <u>bevor</u> Sie die
            Neuberechnung durchführen.
        </p>

        <div class="button"><a href="/project-reports/lcaProcessing/">Komplettes Projekt neu berechnen</a></div>
    </div>

</div>