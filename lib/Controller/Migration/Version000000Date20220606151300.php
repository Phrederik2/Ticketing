<?php

namespace OCA\NotesTutorial\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version000000Date20220606151300 extends SimpleMigrationStep
{

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options)
    {
        /** @var ISchemaWrapper $schema */
        $schema = $schemaClosure();

        $file =  $_SERVER['SCRIPT_FILENAME'] . 'apps/ticketing/templates/TicketingTemplate';
        $file = str_replace('index.php', '', $file);
        $file .= '/index.php';

        include($file);

        customer_Frame::changeSchema();

        return $schema;
    }
}
