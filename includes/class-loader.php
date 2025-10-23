<?php
/**
 * Loader - Manager pro WordPress hooks
 * Centralizovaný systém pro registraci actions a filters
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAW_LMS_Loader {
    
    /**
     * Pole všech zaregistrovaných actions
     */
    protected $actions;
    
    /**
     * Pole všech zaregistrovaných filters
     */
    protected $filters;
    
    /**
     * Konstruktor - inicializace prázdných polí
     */
    public function __construct() {
        $this->actions = array();
        $this->filters = array();
    }
    
    /**
     * Přidání action hooku
     * 
     * @param string $hook          Název WordPress hooku
     * @param object $component     Instance třídy s metodou
     * @param string $callback      Název metody
     * @param int    $priority      Priorita (výchozí 10)
     * @param int    $accepted_args Počet argumentů (výchozí 1)
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * Přidání filter hooku
     * 
     * @param string $hook          Název WordPress hooku
     * @param object $component     Instance třídy s metodou
     * @param string $callback      Název metody
     * @param int    $priority      Priorita (výchozí 10)
     * @param int    $accepted_args Počet argumentů (výchozí 1)
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1) {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }
    
    /**
     * Pomocná metoda pro přidání hooku do pole
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args) {
        $hooks[] = array(
            'hook'          => $hook,
            'component'     => $component,
            'callback'      => $callback,
            'priority'      => $priority,
            'accepted_args' => $accepted_args
        );
        return $hooks;
    }
    
    /**
     * Spuštění - zaregistruje všechny actions a filters ve WordPressu
     */
    public function run() {
        // Registrace všech actions
        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
        
        // Registrace všech filters
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}