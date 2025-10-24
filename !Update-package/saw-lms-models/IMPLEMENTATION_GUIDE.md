# 📘 Implementation Guide - Updating CPT Files

Tento průvodce ukazuje **přesně** jak aktualizovat metodu `save_meta_boxes()` v každém CPT souboru.

---

## 🎯 Cíl

Změnit ukládání z **postmeta** (80+ queries) na **structured table** (1 query).

---

## 📋 Které soubory aktualizovat

1. `includes/post-types/class-course.php`
2. `includes/post-types/class-section.php`
3. `includes/post-types/class-lesson.php`
4. `includes/post-types/class-quiz.php`

---

## 🔧 Změny v `save_meta_boxes()`

### ✅ STARÁ VERZE (nepoužívat)

```php
public static function save_meta_boxes( $post_id, $post ) {
    // Security checks
    if ( ! isset( $_POST['saw_lms_course_nonce'] ) ) {
        return;
    }
    
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_course_nonce'] ) ), 'saw_lms_course_meta' ) ) {
        return;
    }
    
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    
    // Load config
    $fields_config = include SAW_LMS_PLUGIN_DIR . 'includes/config/course-fields.php';
    
    // STARÁ METODA: Loop a uložit každý field zvlášť
    foreach ( $fields_config as $meta_box ) {
        foreach ( $meta_box['fields'] as $field_key => $field ) {
            $value = isset( $_POST[ $field_key ] ) ? $_POST[ $field_key ] : $field['default'];
            update_post_meta( $post_id, $field_key, $value ); // ← 80x toto!
        }
    }
}
```

### ✨ NOVÁ VERZE (použít)

```php
public static function save_meta_boxes( $post_id, $post ) {
    // Security checks (STEJNÉ jako předtím)
    if ( ! isset( $_POST['saw_lms_course_nonce'] ) ) {
        return;
    }
    
    if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_course_nonce'] ) ), 'saw_lms_course_meta' ) ) {
        return;
    }
    
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }
    
    // Load config (STEJNÉ jako předtím)
    $fields_config = include SAW_LMS_PLUGIN_DIR . 'includes/config/course-fields.php';
    
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ⬇️ NOVÁ ČÁST: Collect all data do 1 array
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    $data = array();
    
    foreach ( $fields_config as $meta_box ) {
        foreach ( $meta_box['fields'] as $field_key => $field ) {
            // 1️⃣ Odstranit prefix '_saw_lms_' z názvu pole
            //    '_saw_lms_price' → 'price'
            $column_name = str_replace( '_saw_lms_', '', $field_key );
            
            // 2️⃣ Sanitizovat hodnotu podle typu fieldu
            if ( 'checkbox' === $field['type'] ) {
                // Checkbox: 1 nebo 0
                $data[ $column_name ] = isset( $_POST[ $field_key ] ) ? 1 : 0;
                
            } elseif ( 'number' === $field['type'] ) {
                // Number: floatval
                $data[ $column_name ] = isset( $_POST[ $field_key ] ) 
                    ? floatval( $_POST[ $field_key ] ) 
                    : ( isset( $field['default'] ) ? floatval( $field['default'] ) : 0 );
                
            } elseif ( 'repeater' === $field['type'] || 'json' === $field['type'] ) {
                // JSON fields: zůstane jako array, model ho zakóduje
                $data[ $column_name ] = isset( $_POST[ $field_key ] ) 
                    ? $_POST[ $field_key ] 
                    : array();
                
            } else {
                // Text, textarea, select, atd.
                $data[ $column_name ] = isset( $_POST[ $field_key ] ) 
                    ? sanitize_text_field( wp_unslash( $_POST[ $field_key ] ) ) 
                    : ( isset( $field['default'] ) ? $field['default'] : '' );
            }
        }
    }
    
    // 3️⃣ Uložit JEDNÍM voláním do structured table
    SAW_LMS_Course_Model::save( $post_id, $data );
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ⬆️ KONEC NOVÉ ČÁSTI
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
}
```

---

## 🔄 Přidání Delete Hook

**Přidat NA KONEC konstruktoru každého CPT:**

### class-course.php

```php
private function __construct() {
    // Existing hooks...
    add_action( 'init', array( $this, 'register_post_type' ) );
    add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
    add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );
    // ... atd.
    
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    // ⬇️ PŘIDAT TENTO HOOK
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
    add_action( 'before_delete_post', array( $this, 'delete_structured_data' ) );
    // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
}

/**
 * Delete structured data when post is deleted
 *
 * NEW in v3.0.0: Cleanup structured table when course is deleted.
 *
 * @since 3.0.0
 * @param int $post_id Post ID being deleted.
 */
public function delete_structured_data( $post_id ) {
    if ( self::POST_TYPE !== get_post_type( $post_id ) ) {
        return;
    }
    
    // Delete from structured table
    SAW_LMS_Course_Model::delete( $post_id );
}
```

### class-section.php

```php
// Stejné jako výše, ale použij:
SAW_LMS_Section_Model::delete( $post_id );
```

### class-lesson.php

```php
// Stejné jako výše, ale použij:
SAW_LMS_Lesson_Model::delete( $post_id );
```

### class-quiz.php

```php
// Stejné jako výše, ale použij:
SAW_LMS_Quiz_Model::delete( $post_id );
```

---

## 📝 Kompletní příklad: class-course.php

Zde je **kompletní** ukázka `save_meta_boxes()` + `delete_structured_data()`:

```php
<?php
/**
 * Course Custom Post Type
 *
 * UPDATED in v3.0.0: Using structured database tables.
 */
class SAW_LMS_Course {
    
    const POST_TYPE = 'saw_course';
    private static $instance = null;
    private $fields_config = array();
    
    public static function init() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Load fields config
        $config_file = SAW_LMS_PLUGIN_DIR . 'includes/config/course-fields.php';
        if ( file_exists( $config_file ) ) {
            $this->fields_config = include $config_file;
        }
        
        // Register hooks
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_boxes' ), 10, 2 );
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_admin_columns' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_admin_columns' ), 10, 2 );
        
        // NEW in v3.0.0: Delete hook
        add_action( 'before_delete_post', array( $this, 'delete_structured_data' ) );
    }
    
    // ... ostatní metody (register_post_type, add_meta_boxes, atd.) ...
    
    /**
     * Save meta box data
     *
     * UPDATED in v3.0.0: Uses SAW_LMS_Course_Model for structured storage.
     *
     * @since 2.1.0
     * @param int     $post_id Post ID.
     * @param WP_Post $post    Post object.
     */
    public function save_meta_boxes( $post_id, $post ) {
        // Security checks
        if ( ! isset( $_POST['saw_lms_course_nonce'] ) ) {
            return;
        }
        
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['saw_lms_course_nonce'] ) ), 'saw_lms_course_meta' ) ) {
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }
        
        // Collect all data
        $data = array();
        
        foreach ( $this->fields_config as $meta_box ) {
            if ( ! isset( $meta_box['fields'] ) ) {
                continue;
            }
            
            foreach ( $meta_box['fields'] as $field_key => $field ) {
                // Remove prefix to get column name
                $column_name = str_replace( '_saw_lms_', '', $field_key );
                
                // Sanitize based on field type
                if ( 'checkbox' === $field['type'] ) {
                    $data[ $column_name ] = isset( $_POST[ $field_key ] ) ? 1 : 0;
                    
                } elseif ( 'number' === $field['type'] ) {
                    $data[ $column_name ] = isset( $_POST[ $field_key ] ) 
                        ? floatval( $_POST[ $field_key ] ) 
                        : ( isset( $field['default'] ) ? floatval( $field['default'] ) : 0 );
                    
                } elseif ( 'repeater' === $field['type'] || 'json' === $field['type'] ) {
                    $data[ $column_name ] = isset( $_POST[ $field_key ] ) 
                        ? $_POST[ $field_key ] 
                        : array();
                    
                } else {
                    $data[ $column_name ] = isset( $_POST[ $field_key ] ) 
                        ? sanitize_text_field( wp_unslash( $_POST[ $field_key ] ) ) 
                        : ( isset( $field['default'] ) ? $field['default'] : '' );
                }
            }
        }
        
        // Save to structured table (1 query instead of 80+)
        SAW_LMS_Course_Model::save( $post_id, $data );
        
        /**
         * Fires after course meta is saved.
         *
         * @since 2.1.0
         * @param int     $post_id Post ID.
         * @param WP_Post $post    Post object.
         * @param array   $data    Saved data.
         */
        do_action( 'saw_lms_course_meta_saved', $post_id, $post, $data );
    }
    
    /**
     * Delete structured data when post is deleted
     *
     * NEW in v3.0.0: Cleanup structured table.
     *
     * @since 3.0.0
     * @param int $post_id Post ID being deleted.
     */
    public function delete_structured_data( $post_id ) {
        if ( self::POST_TYPE !== get_post_type( $post_id ) ) {
            return;
        }
        
        SAW_LMS_Course_Model::delete( $post_id );
    }
    
    // ... ostatní metody (add_admin_columns, render_admin_columns, atd.) ...
}
```

---

## 🎯 Mapování Modelů na CPT

| CPT Soubor | Model Třída | Delete Hook |
|-----------|------------|-------------|
| `class-course.php` | `SAW_LMS_Course_Model` | `SAW_LMS_Course_Model::delete()` |
| `class-section.php` | `SAW_LMS_Section_Model` | `SAW_LMS_Section_Model::delete()` |
| `class-lesson.php` | `SAW_LMS_Lesson_Model` | `SAW_LMS_Lesson_Model::delete()` |
| `class-quiz.php` | `SAW_LMS_Quiz_Model` | `SAW_LMS_Quiz_Model::delete()` |

---

## ⚡ Performance Gain

### Před (postmeta):

```php
// 80+ SQL queries
update_post_meta( $post_id, '_saw_lms_price', '49.99' );
update_post_meta( $post_id, '_saw_lms_currency', 'USD' );
update_post_meta( $post_id, '_saw_lms_duration_minutes', '180' );
// ... 77 dalších ...
```

### Po (structured table):

```php
// 1 SQL query
SAW_LMS_Course_Model::save( $post_id, array(
    'price' => 49.99,
    'currency' => 'USD',
    'duration_minutes' => 180,
    // ... všechny najednou ...
) );
```

**Výsledek:** 80x rychlejší! ⚡

---

## ✅ Checklist

Pro každý CPT soubor:

- [ ] Načti fields config v konstruktoru
- [ ] Upravit `save_meta_boxes()` - collect data do array
- [ ] Použij správný Model pro save (`SAW_LMS_Course_Model::save()`, atd.)
- [ ] Přidat `delete_structured_data()` metodu
- [ ] Přidat `before_delete_post` hook v konstruktoru
- [ ] Použij správný Model pro delete
- [ ] Otestuj vytvoření nového postu
- [ ] Otestuj smazání postu
- [ ] Zkontroluj že data jsou v structured table

---

## 🧪 Testování

```php
// Test 1: Vytvoř nový kurz
// WP Admin → SAW LMS → Add New Course
// Vyplň data, publikuj

// Test 2: Zkontroluj data
SELECT * FROM wp_saw_lms_courses WHERE post_id = 123;
// Mělo by vrátit 1 řádek se všemi daty

// Test 3: Smaž kurz
// WP Admin → Trash → Delete Permanently

// Test 4: Ověř cleanup
SELECT * FROM wp_saw_lms_courses WHERE post_id = 123;
// Mělo by vrátit 0 řádků
```

---

## 📞 Potřebuješ pomoct?

Pokud narazíš na problém:

1. Zkontroluj že všechny modely jsou načteny:
   ```php
   var_dump( SAW_LMS_Model_Loader::get_loaded_models() );
   ```

2. Zkontroluj error log:
   ```bash
   tail -f wp-content/uploads/saw-lms/logs/saw-lms-*.log
   ```

3. Zkontroluj že tabulka existuje:
   ```sql
   SHOW TABLES LIKE 'wp_saw_lms_courses';
   DESCRIBE wp_saw_lms_courses;
   ```

---

**Verze:** 3.0.0  
**Datum:** 2025-01-23  
**Autor:** SAW Development Team

**🚀 S tímto průvodcem zvládneš aktualizovat všechny CPT soubory!**
