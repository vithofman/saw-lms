<?php
/**
 * Cache Test Page
 * 
 * Simple admin page to test and demonstrate cache system functionality
 *
 * @package    SAW_LMS
 * @subpackage SAW_LMS/admin
 * @since      1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class SAW_LMS_Cache_Test_Page {
    
    private $plugin_name;
    private $version;
    
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    
    /**
     * Add test page to admin menu
     */
    public function add_test_page() {
        add_submenu_page(
            'saw-lms',
            __('Cache Test', 'saw-lms'),
            __('🧪 Cache Test', 'saw-lms'),
            'manage_options',
            'saw-lms-cache-test',
            array($this, 'display_test_page')
        );
    }
    
    /**
     * Display test page
     */
    public function display_test_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('Nemáte oprávnění přistupovat k této stránce.', 'saw-lms'));
        }
        
        // Handle test actions
        if (isset($_POST['run_test']) && check_admin_referer('saw_lms_cache_test')) {
            $this->run_tests();
        }
        
        $cache = saw_lms_cache();
        
        ?>
        <div class="wrap">
            <h1>🧪 <?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <!-- Cache Status -->
            <div style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #2271b1;">
                <h2><?php _e('Cache System Status', 'saw-lms'); ?></h2>
                <table class="widefat" style="max-width: 600px;">
                    <tr>
                        <td><strong><?php _e('Aktivní Driver:', 'saw-lms'); ?></strong></td>
                        <td><code><?php echo esc_html($cache->get_driver_name()); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Dostupný:', 'saw-lms'); ?></strong></td>
                        <td>
                            <?php if ($cache->is_available()): ?>
                                <span style="color: #00a32a;">✓ Ano</span>
                            <?php else: ?>
                                <span style="color: #d63638;">✗ Ne</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            
            <!-- Quick Test Form -->
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2><?php _e('🚀 Rychlý Test', 'saw-lms'); ?></h2>
                <p><?php _e('Klikni na tlačítko pro spuštění základních testů cache systému.', 'saw-lms'); ?></p>
                
                <form method="post">
                    <?php wp_nonce_field('saw_lms_cache_test'); ?>
                    <input type="hidden" name="run_test" value="1">
                    <?php submit_button(__('Spustit Testy', 'saw-lms'), 'primary', 'submit', false); ?>
                </form>
            </div>
            
            <!-- Manual Test -->
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2><?php _e('🔧 Manuální Test', 'saw-lms'); ?></h2>
                <p><?php _e('Zkus následující operace:', 'saw-lms'); ?></p>
                
                <?php
                // Simple inline test
                $test_key = 'saw_lms_manual_test_' . time();
                $test_value = 'Test hodnota: ' . date('H:i:s');
                
                // SET
                $set_result = $cache->set($test_key, $test_value, 300);
                
                // GET
                $get_result = $cache->get($test_key);
                
                // DELETE
                $delete_result = $cache->delete($test_key);
                
                // Verify deletion
                $verify_result = $cache->get($test_key);
                ?>
                
                <table class="widefat" style="max-width: 800px;">
                    <thead>
                        <tr>
                            <th><?php _e('Operace', 'saw-lms'); ?></th>
                            <th><?php _e('Vstup', 'saw-lms'); ?></th>
                            <th><?php _e('Výstup', 'saw-lms'); ?></th>
                            <th><?php _e('Status', 'saw-lms'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>SET</strong></td>
                            <td><code><?php echo esc_html($test_value); ?></code></td>
                            <td><code><?php echo $set_result ? 'true' : 'false'; ?></code></td>
                            <td>
                                <?php if ($set_result): ?>
                                    <span style="color: #00a32a;">✓ OK</span>
                                <?php else: ?>
                                    <span style="color: #d63638;">✗ FAIL</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>GET</strong></td>
                            <td><code><?php echo esc_html($test_key); ?></code></td>
                            <td><code><?php echo esc_html($get_result); ?></code></td>
                            <td>
                                <?php if ($get_result === $test_value): ?>
                                    <span style="color: #00a32a;">✓ OK</span>
                                <?php else: ?>
                                    <span style="color: #d63638;">✗ FAIL</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>DELETE</strong></td>
                            <td><code><?php echo esc_html($test_key); ?></code></td>
                            <td><code><?php echo $delete_result ? 'true' : 'false'; ?></code></td>
                            <td>
                                <?php if ($delete_result): ?>
                                    <span style="color: #00a32a;">✓ OK</span>
                                <?php else: ?>
                                    <span style="color: #d63638;">✗ FAIL</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>VERIFY DELETE</strong></td>
                            <td><code><?php echo esc_html($test_key); ?></code></td>
                            <td><code><?php echo $verify_result === false ? 'false' : esc_html($verify_result); ?></code></td>
                            <td>
                                <?php if ($verify_result === false): ?>
                                    <span style="color: #00a32a;">✓ OK (smazáno)</span>
                                <?php else: ?>
                                    <span style="color: #d63638;">✗ FAIL (stále existuje)</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <p style="margin-top: 15px;">
                    <em><?php _e('Tato tabulka se aktualizuje při každém načtení stránky.', 'saw-lms'); ?></em>
                </p>
            </div>
            
            <!-- Database Cache Info -->
            <?php if ($cache->get_driver_name() === 'database'): ?>
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2><?php _e('📊 Database Cache Statistiky', 'saw-lms'); ?></h2>
                <?php
                global $wpdb;
                $table = $wpdb->prefix . 'saw_lms_cache';
                
                $total = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
                $active = $wpdb->get_var("SELECT COUNT(*) FROM {$table} WHERE expires_at > NOW()");
                $expired = $total - $active;
                $size = $wpdb->get_var("SELECT SUM(LENGTH(cache_value)) FROM {$table}");
                ?>
                <table class="widefat" style="max-width: 600px;">
                    <tr>
                        <td><strong><?php _e('Celkem záznamů:', 'saw-lms'); ?></strong></td>
                        <td><?php echo number_format($total); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Aktivní:', 'saw-lms'); ?></strong></td>
                        <td style="color: #00a32a;"><?php echo number_format($active); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Expirované:', 'saw-lms'); ?></strong></td>
                        <td style="color: #d63638;"><?php echo number_format($expired); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Velikost:', 'saw-lms'); ?></strong></td>
                        <td><?php echo SAW_LMS_Cache_Helper::format_size($size); ?></td>
                    </tr>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Driver Tests -->
            <div style="background: #fff; padding: 20px; margin: 20px 0;">
                <h2><?php _e('🔍 Dostupnost Driverů', 'saw-lms'); ?></h2>
                <?php
                $test_results = $cache->test_drivers();
                ?>
                <table class="widefat" style="max-width: 800px;">
                    <thead>
                        <tr>
                            <th><?php _e('Driver', 'saw-lms'); ?></th>
                            <th><?php _e('Dostupný', 'saw-lms'); ?></th>
                            <th><?php _e('Funkční', 'saw-lms'); ?></th>
                            <th><?php _e('Poznámka', 'saw-lms'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($test_results as $driver_name => $result): ?>
                        <tr>
                            <td><strong><?php echo esc_html(ucfirst($driver_name)); ?></strong></td>
                            <td>
                                <?php if ($result['available']): ?>
                                    <span style="color: #00a32a;">✓ Ano</span>
                                <?php else: ?>
                                    <span style="color: #d63638;">✗ Ne</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($result['functional']): ?>
                                    <span style="color: #00a32a;">✓ Ano</span>
                                <?php else: ?>
                                    <span style="color: #999;">— N/A</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($result['error'])): ?>
                                    <code style="color: #d63638;"><?php echo esc_html($result['error']); ?></code>
                                <?php elseif ($cache->get_driver_name() === $driver_name): ?>
                                    <span style="color: #2271b1;"><strong>← Aktuálně aktivní</strong></span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div style="background: #d5f4e6; padding: 15px; margin: 20px 0; border-left: 4px solid #00a32a;">
                <h3><?php _e('✅ Cache systém funguje!', 'saw-lms'); ?></h3>
                <p><?php _e('Pokud vidíš zelené checkmarky (✓) v tabulce výše, vše funguje správně.', 'saw-lms'); ?></p>
                <p><?php _e('Cache automaticky zrychluje:', 'saw-lms'); ?></p>
                <ul>
                    <li>✅ Načítání enrollment dat</li>
                    <li>✅ Výpočet pokroku v kurzech</li>
                    <li>✅ Získávání bodů uživatelů</li>
                    <li>✅ A další databázové operace</li>
                </ul>
            </div>
        </div>
        
        <style>
            .widefat td, .widefat th {
                padding: 12px !important;
            }
        </style>
        <?php
    }
    
    /**
     * Run comprehensive tests
     */
    private function run_tests() {
        $cache = saw_lms_cache();
        $results = array();
        
        // Test 1: Basic SET/GET
        $test_value = array('test' => 'data', 'time' => time());
        $cache->set('test_basic', $test_value, 300);
        $get_result = $cache->get('test_basic');
        $results['basic'] = ($get_result == $test_value);
        
        // Test 2: Remember
        $remember_result = $cache->remember('test_remember', 300, function() {
            return 'generated_value';
        });
        $results['remember'] = ($remember_result === 'generated_value');
        
        // Test 3: Multiple
        $multi_values = array('key1' => 'val1', 'key2' => 'val2');
        $cache->set_multiple($multi_values, 300);
        $multi_result = $cache->get_multiple(array('key1', 'key2'));
        $results['multiple'] = (count($multi_result) === 2);
        
        // Test 4: Increment
        $cache->set('counter', 10, 300);
        $new_val = $cache->increment('counter', 5);
        $results['increment'] = ($new_val === 15);
        
        // Cleanup
        $cache->delete('test_basic');
        $cache->delete('test_remember');
        $cache->delete('key1');
        $cache->delete('key2');
        $cache->delete('counter');
        
        // Display results
        $all_passed = !in_array(false, $results, true);
        
        if ($all_passed) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Všechny testy prošly!</strong> Cache systém funguje perfektně.</p></div>';
        } else {
            echo '<div class="notice notice-error is-dismissible"><p><strong>❌ Některé testy selhaly.</strong> Zkontroluj logy.</p></div>';
        }
    }
}