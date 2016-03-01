<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'SystemEventManager.class.php';
require_once 'IRunInAMutex.php';

abstract class SystemEventProcessor implements IRunInAMutex {

    /**
     * @var SystemEventProcess
     */
    protected $process;

    /**
     * @var SystemEventManager
     */
    protected $system_event_manager;

    /**
     * @var SystemEventDao
     */
    protected $dao;

    /**
     * @var Logger
     */
    protected $logger;

    public function __construct(
        SystemEventProcess $process,
        SystemEventManager $system_event_manager,
        SystemEventDao $dao,
        Logger $logger
    ) {
        $this->process              = $process;
        $this->system_event_manager = $system_event_manager;
        $this->dao                  = $dao;
        $this->logger               = $logger;
    }

    public function getProcess() {
        return $this->process;
    }

    public function execute($queue) {
        $executed_events_ids = $this->loopOverEventsForOwner($this->getOwner(), $queue);
        try {
            $this->postEventsActions($executed_events_ids, $queue);
        } catch(Exception $exception) {
            $this->logger->error("[SystemEventProcessor] An error happened during execution of post actions: ".$exception->getMessage());
        }
    }

    protected function loopOverEventsForOwner($owner, $queue) {
        $types = $this->system_event_manager->getTypesForQueue($queue);
        if (! $types) {
            return array();
        }

        $executed_events_ids = array();
        while (($dar=$this->dao->checkOutNextEvent($owner, $types)) != null) {
            $sysevent = $this->getSystemEventFromDar($dar);
            if ($sysevent) {
                $this->executeSystemEvent($sysevent);
                $executed_events_ids[] = $sysevent->getId();
            }
        }

        return $executed_events_ids;
    }

    private function getSystemEventFromDar($dar) {
        if ($row = $dar->getRow()) {
            return $this->system_event_manager->getInstanceFromRow($row);
        }
        return null;
    }

    private function executeSystemEvent(SystemEvent $sysevent) {
        $this->logger->info("Processing event #".$sysevent->getId()." ".$sysevent->getType()."(".$sysevent->getParameters().")");
        try {
            $sysevent->process();
        } catch (Exception $exception) {
            $sysevent->logException($exception);
        }
        $this->dao->close($sysevent);
        $sysevent->notify();
        $this->logger->info("Processing event #".$sysevent->getId().": done.", Backend::LOG_INFO);
        // Output errors???
    }

    abstract protected function getOwner();

    abstract protected function postEventsActions(array $executed_events_ids, $queue_name);

}
