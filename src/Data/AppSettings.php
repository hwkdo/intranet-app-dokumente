<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Data;

use Hwkdo\IntranetAppBase\Data\Attributes\Description;
use Hwkdo\IntranetAppBase\Data\BaseAppSettings;

class AppSettings extends BaseAppSettings
{
    public function __construct(
        #[Description('Warnung X Tage vor Ablauf der Gültigkeit bzw. vor der Jahresprüfung')]
        public int $validityWarningDays = 30,

        #[Description('News-Rahmen: X-Position der Thumbnail-Lücke (Pixel, bezogen auf Rahmenbreite)')]
        public ?int $newsFrameSlotX = null,

        #[Description('News-Rahmen: Y-Position der Thumbnail-Lücke (Pixel)')]
        public ?int $newsFrameSlotY = null,

        #[Description('News-Rahmen: Breite der Thumbnail-Lücke (Pixel)')]
        public ?int $newsFrameSlotWidth = null,

        #[Description('News-Rahmen: Höhe der Thumbnail-Lücke (Pixel)')]
        public ?int $newsFrameSlotHeight = null,

        #[Description('Permission für Dokument-Upload (leer = Config-Default)')]
        public ?string $permissionUpload = null,

        #[Description('Permission für „Zur Kenntnisnahme“ setzen (leer = Config-Default)')]
        public ?string $permissionKenntnisnahme = null,

        #[Description('Permission für manuelle GVP-Auswahl beim Upload (leer = Config-Default)')]
        public ?string $permissionChooseGvp = null,
    ) {}
}
