<?php

namespace App\Services\Integration;

/**
 * Marker interface for services that bridge POS to an external system.
 *
 * Phase 0: contract-only. Implementations are stubs that return null /
 * throw — no real external calls are made until the other systems are audited.
 */
interface ExternalSystemInterface
{
    /** The external system identifier (used in logs/messages). */
    public function systemName(): string;
}
