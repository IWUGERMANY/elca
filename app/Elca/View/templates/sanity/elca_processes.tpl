<div id="content" class="processes overview process-sanity">
    <h3 class="section">Folgende Baustoffkonfigurationen sollten überprüft werden
        <a href="/sanity/processes/refreshSanities/">Liste aktualisieren</a>
    </h3>

    <table id="processSanityTable" class="hover order-column compact">
        <thead>
        <tr>
            <th class="ref-num">_(Kategorie)_</th>
            <th class="process-config">_(Baustoffkonfiguration)_</th>
            <th class="process-db">_(Baustoffdatenbank)_</th>
            <th class="epd-sub-type">_(EPD Subtypen)_</th>
            <th class="epd-modules">_(EPD Module)_</th>
            <th class="error-status">_(Status)_</th>
            <th class="false-positive">_(Ign)_</th>
            <th class="is-reference">_(Sichtb.)_</th>
        </tr>
        </thead>
        <thead>
        <tr class="filter">
            <th class="ref-num"></th>
            <th class="process-config">
                <input type="text" />
            </th>
            <th class="process-db">
                <select id="processDbFilter" name="processDbFilter">
                    <option value="">_(Alle)_</option>
                </select>
            </th>
            <th class="epd-sub-type">
                <select name="epdSubTypeFilter">
                    <option value="">_(Alle)_</option>
                    <option value="_(generic dataset)_">_(generic dataset)_</option>
                    <option value="_(average dataset)_">_(average dataset)_</option>
                    <option value="_(representative dataset)_">_(representative dataset)_</option>
                    <option value="_(specific dataset)_">_(specific dataset)_</option>
                </select>
            </th>
            <th class="epd-modules">
                <select multiple="multiple" name="epdModulesFilter" id="epdModulesFilter">
                    <option value="">_(Alle)_</option>
                    <option class="en-15804" value="A1 - A3">_(A1 - A3)_</option>
                    <option class="en-15804" value="B6">_(B6)_</option>
                    <option class="en-15804" value="C3">_(C3)_</option>
                    <option class="en-15804" value="C4">_(C4)_</option>
                    <option class="en-15804" value="D">_(D)_</option>
                    <option value="_(Herstellung)_">_(Herstellung)_</option>
                    <option value="_(Nutzung)_">_(Nutzung)_</option>
                    <option value="_(Entsorgung)_">_(Entsorgung)_</option>
                </select>
            </th>
            <th class="error-status">
                <select name="statusFilter">
                    <option value="">_(Alle)_</option>
                    <option value="_(MISSING_PRODUCTION)_">_(MISSING_PRODUCTION)_</option>
                    <option value="_(MISSING_EOL)_">_(MISSING_EOL)_</option>
                    <option value="_(MISSING_CONVERSIONS)_">_(MISSING_CONVERSIONS)_</option>
                    <option value="_(MISSING_LIFE_TIME)_">_(MISSING_LIFE_TIME)_</option>
                    <option value="_(MISSING_DENSITY)_">_(MISSING_DENSITY)_</option>
                    <option value="_(MISSING_MASS_CONVERSION)_">_(MISSING_MASS_CONVERSION)_</option>
                    <option value="_(STALE)_">_(STALE)_</option>
                </select>
            </th>
            <th class="false-positive">
                <input type="checkbox" title="_(Filtern nach ausgeblendeten Datensätzen. Doppelklick zum zurücksetzen.)_" />
            </th>
            <th class="is-reference">
                <input type="checkbox" title="_(Filtern nach Sichtbarkeit für Anwender. Doppelklick zum zurücksetzen.)_"/>
            </th>
        </tr>
        </thead>

    </table>
</div>