<?php
declare(strict_types=1);

namespace NeoDashboard\Core;

use NeoDashboard\Core\Logger;

use NeoDashboard\Core\Manager\AjaxManager;
use NeoDashboard\Core\Manager\SidebarManager;
use NeoDashboard\Core\Manager\WidgetManager;
use NeoDashboard\Core\Manager\NotificationManager;
use NeoDashboard\Core\Manager\SectionManager;
use NeoDashboard\Core\Manager\AssetManager;
use NeoDashboard\Core\Manager\ContentManager;

final class Dashboard
{
    public function __construct(
        protected SectionManager      $sectionManager      = new SectionManager(),        
        protected ContentManager      $contentManager      = new ContentManager(),
        protected SidebarManager      $sidebarManager      = new SidebarManager(),
        protected WidgetManager       $widgetManager       = new WidgetManager(),
        protected NotificationManager $notificationManager = new NotificationManager(),
        protected AssetManager        $assetManager        = new AssetManager(),
        protected AjaxManager         $ajaxManager         = new AjaxManager()
    ) {}

    /**
     * Registriert alle Hooks: Assets und Manager‑Registrierungen
     */
    public function registerManagers(): void
    {
        // AssetManager registrieren (enqueue & admin-bar hide)
        $this->assetManager->register();

        // Manager mit Prioritäten definieren
        $managers = [
            [ $this->sectionManager, 5 ],
            [ $this->sidebarManager, 10 ],
            [ $this->widgetManager, 15 ],
            [ $this->notificationManager, 20 ],
            [ $this->ajaxManager, 25 ],
            [ $this->contentManager, 30 ],
        ];

        foreach ( $managers as [$manager, $priority] ) {
            add_action( 'neo_dashboard_pre_init', [ $manager, 'registerDefault' ], $priority );
        }

        Logger::info('Dashboard:registerManagers end');
    }

    /**
     * Führt die Dashboard‑Initialisierung aus: ruft registerHooks() auf
     */
    public function run(): void
    {
        $this->registerManagers();
        do_action('neo_dashboard_pre_init');
        do_action('neo_dashboard_init');
    }
}
