<?php

declare(strict_types=1);

return [
    "UPDATE repair_records SET status = 'pendiente' WHERE status IN ('open', 'pending')",
    "UPDATE repair_records SET status = 'en_curso' WHERE status IN ('in_progress', 'en_progreso')",
    "UPDATE repair_records SET status = 'hecho' WHERE status IN ('closed', 'done', 'completado')",
    "UPDATE repair_records SET status = 'cancelada' WHERE status IN ('cancelled', 'canceled')",
    "ALTER TABLE repair_records MODIFY status VARCHAR(40) NOT NULL DEFAULT 'pendiente'",
];
