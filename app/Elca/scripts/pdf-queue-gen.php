<?php
/**
 * This file is part of the eLCA project
 *
 * eLCA
 * A web based life cycle assessment application
 *
 * Copyright (c) 2016 Tobias Lode <tobias@beibob.de>
 *               BEIBOB Medienfreunde GbR - http://beibob.de/
 *
 * eLCA is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * eLCA is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with eLCA. If not, see <http://www.gnu.org/licenses/>.
 *
 */

use Beibob\Blibs\Environment;
use Elca\Db\ElcaReportSet;


$appDir = realpath('./app');
$config = Environment::getInstance()->getConfig();
try {
	loginfo('Create PDF Reports');
    loginfo('----------------------------------------------------------------');
    loginfo(ElcaReportSet::TABLE_REPORT_PDF_QUEUE);
	loginfo('----------------------------------------------------------------');
	
	$PDF2Create = ElcaReportSet::createPdfInQueue();
	
	if( $PDF2Create->isEmpty() )
	{
		exit();
	}	
	else
	{
		foreach($PDF2Create as $PDFreport)
		{
			exec( $PDFreport->pdf_cmd, $output, $returnvar );
			$initValues = [
				'user_id' => $PDFreport->user_id, 
				'projects_id' => $PDFreport->projects_id, 
				'report_name' => $PDFreport->report_name, 
				'current_variant_id' => $PDFreport->current_variant_id
			];
			ElcaReportSet::setPdfReadyInQueue($initValues);
		}
	}	
}
catch (\Exception $Exception)
{
    logerror($Exception->getMessage());
}





