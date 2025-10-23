\# 🔒 SAW LMS Security Principles



\## 📋 OBSAH

1\. \[Bezpečnostní Baseline](#bezpečnostní-baseline)

2\. \[Validace a Sanitizace Vstupů](#validace-a-sanitizace-vstupů)

3\. \[Escapování Výstupů](#escapování-výstupů)

4\. \[CSRF Ochrana (Nonces)](#csrf-ochrana-nonces)

5\. \[SQL Injection Prevence](#sql-injection-prevence)

6\. \[XSS Prevence](#xss-prevence)

7\. \[Capability Checks](#capability-checks)

8\. \[Rate Limiting](#rate-limiting)

9\. \[Bezpečné Nahrávání Souborů](#bezpečné-nahrávání-souborů)

10\. \[Security Checklist](#security-checklist)



---



\## 🎯 Bezpečnostní Baseline



SAW LMS následuje \*\*OWASP Top 10\*\* principy a WordPress-specific bezpečnostní best practices.



\### OWASP Top 10 (2021) v Kontextu SAW LMS



1\. \*\*A01:2021 – Broken Access Control\*\*

&nbsp;  - ✅ Vždy ověřujeme oprávnění pomocí `current\_user\_can()`

&nbsp;  - ✅ Každý endpoint/akce má capability check

&nbsp;  - ✅ API endpointy mají `permission\_callback`



2\. \*\*A02:2021 – Cryptographic Failures\*\*

&nbsp;  - ✅ Hesla nikdy neukládáme v plain textu

&nbsp;  - ✅ API klíče a tokeny šifrujeme pomocí WordPress salt

&nbsp;  - ✅ Používáme HTTPS pro API komunikaci



3\. \*\*A03:2021 – Injection (SQL, XSS)\*\*

&nbsp;  - ✅ Vždy používáme `$wpdb->prepare()` pro SQL dotazy

&nbsp;  - ✅ Vždy escapujeme výstupy pomocí `esc\_\*` funkcí

&nbsp;  - ✅ Validujeme všechny vstupy



4\. \*\*A04:2021 – Insecure Design\*\*

&nbsp;  - ✅ Security-first přístup při návrhu features

&nbsp;  - ✅ Threat modeling pro kritické funkce (certifikáty, platby)

&nbsp;  - ✅ Fail-safe defaults (deny by default)



5\. \*\*A05:2021 – Security Misconfiguration\*\*

&nbsp;  - ✅ Žádné debug výstupy v produkci

&nbsp;  - ✅ Minimální oprávnění pro soubory/adresáře

&nbsp;  - ✅ Deaktivace nepotřebných features



6\. \*\*A06:2021 – Vulnerable Components\*\*

&nbsp;  - ✅ Pravidelný `composer audit` (GitHub Actions)

&nbsp;  - ✅ Aktualizace závislostí

&nbsp;  - ✅ Monitoring bezpečnostních bulletinů



7\. \*\*A07:2021 – Identification and Authentication Failures\*\*

&nbsp;  - ✅ Rate limiting pro login/API

&nbsp;  - ✅ Validace session tokenů

&nbsp;  - ✅ Bezpečné obnovení hesla



8\. \*\*A08:2021 – Software and Data Integrity Failures\*\*

&nbsp;  - ✅ Verifikace nonces při změnách dat

&nbsp;  - ✅ Audit logy pro kritické akce

&nbsp;  - ✅ Integrita souborů (checksums pro uploads)



9\. \*\*A09:2021 – Security Logging Failures\*\*

&nbsp;  - ✅ Centralizovaný SAW\_LMS\_Logger

&nbsp;  - ✅ Logování všech security events

&nbsp;  - ✅ Monitoring podezřelých aktivit



10\. \*\*A10:2021 – Server-Side Request Forgery (SSRF)\*\*

&nbsp;   - ✅ Whitelist pro externí API volání

&nbsp;   - ✅ Validace URL před HTTP requests

&nbsp;   - ✅ Timeout limity



---



\## 🔍 Validace a Sanitizace Vstupů



\### PRAVIDLO #1: Nikdy nedůvěřuj uživatelskému vstupu



\*\*Všechny zdroje dat MUSÍ být sanitizovány:\*\*

\- `$\_GET`, `$\_POST`, `$\_REQUEST`

\- `$\_FILES`

\- Data z API requestů

\- Data z databáze před použitím v nových dotazech

\- Data z cache před použitím

\- JSON/XML data



\### WordPress Sanitizace Funkce

```php

// ✅ SPRÁVNĚ - Vždy sanitizuj vstupy



// Text input

$course\_name = sanitize\_text\_field( $\_POST\['course\_name'] );



// Textarea

$description = sanitize\_textarea\_field( $\_POST\['description'] );



// Email

$email = sanitize\_email( $\_POST\['email'] );



// URL

$video\_url = esc\_url\_raw( $\_POST\['video\_url'] );



// Integer

$course\_id = absint( $\_POST\['course\_id'] );



// Boolean

$is\_published = (bool) $\_POST\['is\_published'];



// Array of integers

$lesson\_ids = array\_map( 'absint', $\_POST\['lesson\_ids'] );



// Checkbox (0 nebo 1)

$is\_free = isset( $\_POST\['is\_free'] ) ? 1 : 0;



// Enum/Select (whitelist)

$allowed\_types = array( 'video', 'text', 'quiz', 'assignment' );

$lesson\_type = in\_array( $\_POST\['lesson\_type'], $allowed\_types, true ) 

&nbsp;   ? $\_POST\['lesson\_type'] 

&nbsp;   : 'text';



// JSON data

$json\_data = json\_decode( 

&nbsp;   stripslashes( $\_POST\['json\_data'] ), 

&nbsp;   true 

);

if ( json\_last\_error() !== JSON\_ERROR\_NONE ) {

&nbsp;   wp\_send\_json\_error( 'Invalid JSON' );

}

```



\### Custom Validace

```php

// ✅ Validace délky

if ( strlen( $course\_name ) < 3 || strlen( $course\_name ) > 200 ) {

&nbsp;   wp\_send\_json\_error( 'Course name must be 3-200 characters' );

}



// ✅ Validace rozsahu

if ( $price < 0 || $price > 999999 ) {

&nbsp;   wp\_send\_json\_error( 'Invalid price range' );

}



// ✅ Validace regex

if ( ! preg\_match( '/^\[a-zA-Z0-9-\_]+$/', $slug ) ) {

&nbsp;   wp\_send\_json\_error( 'Invalid slug format' );

}



// ✅ Validace existence

$course = get\_post( $course\_id );

if ( ! $course || 'saw\_course' !== $course->post\_type ) {

&nbsp;   wp\_send\_json\_error( 'Course not found' );

}

```



\### ❌ NIKDY NEDĚLEJ

```php

// ❌ ŠPATNĚ - Přímé použití bez sanitizace

$course\_name = $\_POST\['course\_name'];

$wpdb->query( "UPDATE table SET name = '$course\_name'" );



// ❌ ŠPATNĚ - Důvěra v GET parametry

$id = $\_GET\['id'];

delete\_post( $id );



// ❌ ŠPATNĚ - Předpoklad, že hodnota existuje

$value = $\_POST\['field']; // může selhat, pokud 'field' neexistuje

```



---



\## 🖼️ Escapování Výstupů



\### PRAVIDLO #2: Vždy escapuj data před výstupem



\*\*Všechny výstupy MUSÍ být escapovány podle kontextu:\*\*

```php

// ✅ HTML text

<h1><?php echo esc\_html( $course\_name ); ?></h1>



// ✅ HTML atribut

<input type="text" value="<?php echo esc\_attr( $value ); ?>">



// ✅ URL

<a href="<?php echo esc\_url( $course\_url ); ?>">Odkaz</a>



// ✅ JavaScript string

<script>

&nbsp;   var courseName = '<?php echo esc\_js( $course\_name ); ?>';

</script>



// ✅ Textarea

<textarea><?php echo esc\_textarea( $description ); ?></textarea>



// ✅ HTML (povolené tagy) - použij opatrně!

echo wp\_kses\_post( $content ); // Povoluje <p>, <a>, <strong>, atd.



// ✅ Vlastní whitelist tagů

$allowed\_html = array(

&nbsp;   'a' => array( 'href' => array(), 'title' => array() ),

&nbsp;   'strong' => array(),

&nbsp;   'em' => array(),

);

echo wp\_kses( $content, $allowed\_html );



// ✅ JSON output (REST API)

wp\_send\_json\_success( array(

&nbsp;   'course\_id' => absint( $course\_id ),

&nbsp;   'name'      => sanitize\_text\_field( $course\_name ),

&nbsp;   'url'       => esc\_url\_raw( $course\_url ),

) );

```



\### Context-Aware Escapování

```php

// ✅ V HTML kontextu

echo '<div class="course">' . esc\_html( $name ) . '</div>';



// ✅ V atributu

echo '<div data-id="' . esc\_attr( $course\_id ) . '">';



// ✅ V URL

echo '<a href="' . esc\_url( $link ) . '">';



// ✅ V JavaScriptu

echo '<script>alert("' . esc\_js( $message ) . '");</script>';



// ✅ V SQL (prepared statement)

$wpdb->query( $wpdb->prepare(

&nbsp;   "SELECT \* FROM table WHERE name = %s",

&nbsp;   $name

) );

```



\### ❌ NIKDY NEDĚLAJ

```php

// ❌ ŠPATNĚ - Žádné escapování

echo $user\_input;



// ❌ ŠPATNĚ - Špatný kontext

echo '<a href="' . esc\_html( $url ) . '">'; // Mělo by být esc\_url!



// ❌ ŠPATNĚ - Dvojité escapování

echo esc\_html( esc\_html( $text ) ); // Zbytečné

```



---



\## 🔐 CSRF Ochrana (Nonces)



\### PRAVIDLO #3: Každá stavová akce musí mít nonce



\*\*Použij nonces pro:\*\*

\- Všechny formuláře měnící data

\- Všechny AJAX akce měnící stav

\- Všechny DELETE/UPDATE operace

\- Admin akce (bulk actions, quick edit)



\### Formuláře (PHP)

```php

// ✅ Vygenerování nonce

<form method="POST">

&nbsp;   <?php wp\_nonce\_field( 'saw\_lms\_save\_course', 'saw\_lms\_course\_nonce' ); ?>

&nbsp;   <input type="text" name="course\_name">

&nbsp;   <button type="submit">Uložit</button>

</form>



// ✅ Verifikace nonce

if ( ! isset( $\_POST\['saw\_lms\_course\_nonce'] ) || 

&nbsp;    ! wp\_verify\_nonce( $\_POST\['saw\_lms\_course\_nonce'], 'saw\_lms\_save\_course' ) ) {

&nbsp;   wp\_die( 'Security check failed' );

}

```



\### AJAX Requests (JavaScript)

```php

// ✅ PHP - Předání nonce do JS

wp\_localize\_script( 'saw-lms-admin', 'sawLmsAjax', array(

&nbsp;   'ajax\_url' => admin\_url( 'admin-ajax.php' ),

&nbsp;   'nonce'    => wp\_create\_nonce( 'saw\_lms\_ajax\_nonce' ),

) );

```

```javascript

// ✅ JavaScript - Odeslání nonce s AJAX

jQuery.ajax({

&nbsp;   url: sawLmsAjax.ajax\_url,

&nbsp;   type: 'POST',

&nbsp;   data: {

&nbsp;       action: 'saw\_lms\_save\_lesson',

&nbsp;       nonce: sawLmsAjax.nonce,

&nbsp;       lesson\_data: lessonData

&nbsp;   },

&nbsp;   success: function(response) {

&nbsp;       // ...

&nbsp;   }

});

```

```php

// ✅ PHP - Verifikace AJAX nonce

add\_action( 'wp\_ajax\_saw\_lms\_save\_lesson', 'saw\_lms\_ajax\_save\_lesson' );



function saw\_lms\_ajax\_save\_lesson() {

&nbsp;   // Verify nonce

&nbsp;   if ( ! isset( $\_POST\['nonce'] ) || 

&nbsp;        ! wp\_verify\_nonce( $\_POST\['nonce'], 'saw\_lms\_ajax\_nonce' ) ) {

&nbsp;       wp\_send\_json\_error( 'Invalid nonce' );

&nbsp;   }

&nbsp;   

&nbsp;   // Verify capabilities

&nbsp;   if ( ! current\_user\_can( 'edit\_saw\_courses' ) ) {

&nbsp;       wp\_send\_json\_error( 'Insufficient permissions' );

&nbsp;   }

&nbsp;   

&nbsp;   // Process request...

}

```



\### REST API Endpoints

```php

// ✅ Nonce v REST API

register\_rest\_route( 'saw-lms/v1', '/courses/(?P<id>\\d+)', array(

&nbsp;   'methods'             => 'POST',

&nbsp;   'callback'            => 'saw\_lms\_update\_course',

&nbsp;   'permission\_callback' => function() {

&nbsp;       return current\_user\_can( 'edit\_saw\_courses' );

&nbsp;   },

&nbsp;   // Nonce je automaticky ověřen přes cookie authentication

) );

```



\### ❌ NIKDY NEDĚLEJ

```php

// ❌ ŠPATNĚ - Žádná ochrana

if ( isset( $\_POST\['delete\_course'] ) ) {

&nbsp;   wp\_delete\_post( $\_POST\['course\_id'] );

}



// ❌ ŠPATNĚ - Použití stejného nonce pro všechno

wp\_nonce\_field( 'generic\_nonce', 'nonce' ); // Buď specifický!

```



---



\## 💉 SQL Injection Prevence



\### PRAVIDLO #4: Vždy používej $wpdb->prepare()



\*\*NIKDY nepoužívej string concatenation v SQL dotazech!\*\*

```php

// ✅ SPRÁVNĚ - Prepared statement

global $wpdb;



// Single value

$results = $wpdb->get\_results( $wpdb->prepare(

&nbsp;   "SELECT \* FROM {$wpdb->prefix}saw\_lms\_enrollments WHERE user\_id = %d",

&nbsp;   $user\_id

) );



// Multiple values

$wpdb->insert(

&nbsp;   $wpdb->prefix . 'saw\_lms\_enrollments',

&nbsp;   array(

&nbsp;       'user\_id'    => $user\_id,

&nbsp;       'course\_id'  => $course\_id,

&nbsp;       'status'     => $status,

&nbsp;       'created\_at' => current\_time( 'mysql' ),

&nbsp;   ),

&nbsp;   array( '%d', '%d', '%s', '%s' ) // Data types

);



// UPDATE

$wpdb->update(

&nbsp;   $wpdb->prefix . 'saw\_lms\_progress',

&nbsp;   array( 'completed' => 1, 'completed\_at' => current\_time( 'mysql' ) ),

&nbsp;   array( 'user\_id' => $user\_id, 'lesson\_id' => $lesson\_id ),

&nbsp;   array( '%d', '%s' ), // Data types pro SET

&nbsp;   array( '%d', '%d' )  // Data types pro WHERE

);



// IN clause

$ids = array\_map( 'absint', $\_POST\['course\_ids'] );

$placeholders = implode( ',', array\_fill( 0, count( $ids ), '%d' ) );

$query = $wpdb->prepare(

&nbsp;   "SELECT \* FROM {$wpdb->prefix}saw\_lms\_courses WHERE id IN ($placeholders)",

&nbsp;   ...$ids // Spread operator

);



// LIKE

$search = '%' . $wpdb->esc\_like( $search\_term ) . '%';

$results = $wpdb->get\_results( $wpdb->prepare(

&nbsp;   "SELECT \* FROM {$wpdb->prefix}saw\_lms\_courses WHERE name LIKE %s",

&nbsp;   $search

) );

```



\### Placeholders



\- `%d` - Integer

\- `%f` - Float

\- `%s` - String



\### ❌ NIKDY NEDĚLEJ

```php

// ❌ ŠPATNĚ - String concatenation

$query = "SELECT \* FROM table WHERE id = " . $id;

$wpdb->query( $query );



// ❌ ŠPATNĚ - User input přímo v dotazu

$query = "SELECT \* FROM table WHERE name = '" . $\_POST\['name'] . "'";



// ❌ ŠPATNĚ - Escaped manually (nepoužívej!)

$name = addslashes( $\_POST\['name'] );

$query = "SELECT \* FROM table WHERE name = '$name'";

```



---



\## 🛡️ XSS Prevence



\### PRAVIDLO #5: Kombinuj sanitizaci vstupů + escapování výstupů



\*\*XSS (Cross-Site Scripting) je prevencí na DVOU úrovních:\*\*



\### 1. Při Ukládání (Input)

```php

// ✅ Sanitizuj při ukládání

$title = sanitize\_text\_field( $\_POST\['title'] );

$description = sanitize\_textarea\_field( $\_POST\['description'] );



// ✅ HTML content - použij wp\_kses

$lesson\_content = wp\_kses\_post( $\_POST\['content'] ); // Povolené HTML tagy



update\_post\_meta( $post\_id, '\_saw\_lms\_title', $title );

```



\### 2. Při Zobrazování (Output)

```php

// ✅ Escapuj při výstupu

$title = get\_post\_meta( $post\_id, '\_saw\_lms\_title', true );

echo '<h1>' . esc\_html( $title ) . '</h1>';



// ✅ V atributech

echo '<div data-title="' . esc\_attr( $title ) . '">';



// ✅ V JavaScript

echo '<script>var title = "' . esc\_js( $title ) . '";</script>';

```



\### Rich Text (TinyMCE/Gutenberg)

```php

// ✅ Povolené HTML tagy pro lesson content

$allowed\_lesson\_html = array(

&nbsp;   'p'      => array(),

&nbsp;   'br'     => array(),

&nbsp;   'strong' => array(),

&nbsp;   'em'     => array(),

&nbsp;   'a'      => array( 'href' => array(), 'title' => array(), 'target' => array() ),

&nbsp;   'ul'     => array(),

&nbsp;   'ol'     => array(),

&nbsp;   'li'     => array(),

&nbsp;   'h2'     => array(),

&nbsp;   'h3'     => array(),

&nbsp;   'h4'     => array(),

&nbsp;   'img'    => array( 'src' => array(), 'alt' => array(), 'width' => array(), 'height' => array() ),

&nbsp;   'iframe' => array( 'src' => array(), 'width' => array(), 'height' => array(), 'frameborder' => array() ),

);



$lesson\_content = wp\_kses( $\_POST\['lesson\_content'], $allowed\_lesson\_html );

```



\### Admin vs Frontend

```php

// ✅ Admin - více povolených tagů (wp\_kses\_post)

if ( is\_admin() \&\& current\_user\_can( 'edit\_saw\_courses' ) ) {

&nbsp;   $content = wp\_kses\_post( $\_POST\['content'] );

} else {

&nbsp;   // ✅ Frontend - strict whitelist

&nbsp;   $content = wp\_kses( $\_POST\['content'], $allowed\_lesson\_html );

}

```



\### ❌ NIKDY NEDĚLEJ

```php

// ❌ ŠPATNĚ - Přímý výstup bez escapování

echo $user\_input;



// ❌ ŠPATNĚ - strip\_tags není dostatečný

echo strip\_tags( $user\_input ); // Lze obejít!



// ❌ ŠPATNĚ - htmlentities místo esc\_html

echo htmlentities( $text ); // Nekompatibilní s WordPress

```



---



\## 👮 Capability Checks



\### PRAVIDLO #6: Každá akce musí ověřit oprávnění



\*\*Nikdy nepředpokládej, že uživatel má oprávnění!\*\*



\### Admin Actions

```php

// ✅ Kontrola před akcí

function saw\_lms\_save\_course\_meta( $post\_id ) {

&nbsp;   // Nonce check

&nbsp;   if ( ! isset( $\_POST\['saw\_lms\_course\_nonce'] ) || 

&nbsp;        ! wp\_verify\_nonce( $\_POST\['saw\_lms\_course\_nonce'], 'saw\_lms\_save\_course' ) ) {

&nbsp;       return;

&nbsp;   }

&nbsp;   

&nbsp;   // Capability check

&nbsp;   if ( ! current\_user\_can( 'edit\_saw\_courses' ) ) {

&nbsp;       return;

&nbsp;   }

&nbsp;   

&nbsp;   // Autosave check

&nbsp;   if ( defined( 'DOING\_AUTOSAVE' ) \&\& DOING\_AUTOSAVE ) {

&nbsp;       return;

&nbsp;   }

&nbsp;   

&nbsp;   // Save meta...

}

```



\### AJAX Actions

```php

// ✅ AJAX - Capability check

add\_action( 'wp\_ajax\_saw\_lms\_delete\_lesson', 'saw\_lms\_ajax\_delete\_lesson' );



function saw\_lms\_ajax\_delete\_lesson() {

&nbsp;   // Nonce check

&nbsp;   check\_ajax\_referer( 'saw\_lms\_ajax\_nonce', 'nonce' );

&nbsp;   

&nbsp;   // Capability check

&nbsp;   if ( ! current\_user\_can( 'delete\_saw\_lessons' ) ) {

&nbsp;       wp\_send\_json\_error( 'Insufficient permissions' );

&nbsp;   }

&nbsp;   

&nbsp;   $lesson\_id = absint( $\_POST\['lesson\_id'] );

&nbsp;   

&nbsp;   // Additional check - vlastník?

&nbsp;   $lesson = get\_post( $lesson\_id );

&nbsp;   if ( $lesson->post\_author != get\_current\_user\_id() \&\& ! current\_user\_can( 'delete\_others\_saw\_lessons' ) ) {

&nbsp;       wp\_send\_json\_error( 'You can only delete your own lessons' );

&nbsp;   }

&nbsp;   

&nbsp;   // Delete...

}

```



\### REST API Endpoints

```php

// ✅ REST API - Permission callback

register\_rest\_route( 'saw-lms/v1', '/courses/(?P<id>\\d+)', array(

&nbsp;   'methods'             => 'DELETE',

&nbsp;   'callback'            => 'saw\_lms\_delete\_course',

&nbsp;   'permission\_callback' => function( $request ) {

&nbsp;       $course\_id = (int) $request\['id'];

&nbsp;       $course = get\_post( $course\_id );

&nbsp;       

&nbsp;       // Check if user can delete this specific course

&nbsp;       if ( ! $course || 'saw\_course' !== $course->post\_type ) {

&nbsp;           return false;

&nbsp;       }

&nbsp;       

&nbsp;       if ( ! current\_user\_can( 'delete\_saw\_courses' ) ) {

&nbsp;           return false;

&nbsp;       }

&nbsp;       

&nbsp;       // Additional check - vlastník nebo admin?

&nbsp;       if ( $course->post\_author != get\_current\_user\_id() \&\& ! current\_user\_can( 'delete\_others\_saw\_courses' ) ) {

&nbsp;           return false;

&nbsp;       }

&nbsp;       

&nbsp;       return true;

&nbsp;   },

&nbsp;   'args' => array(

&nbsp;       'id' => array(

&nbsp;           'validate\_callback' => function( $param, $request, $key ) {

&nbsp;               return is\_numeric( $param );

&nbsp;           }

&nbsp;       ),

&nbsp;   ),

) );

```



\### Custom Capabilities

```php

// ✅ Registrace vlastních capabilities

function saw\_lms\_add\_capabilities() {

&nbsp;   $admin = get\_role( 'administrator' );

&nbsp;   $admin->add\_cap( 'manage\_saw\_lms' );

&nbsp;   $admin->add\_cap( 'edit\_saw\_courses' );

&nbsp;   $admin->add\_cap( 'delete\_saw\_courses' );

&nbsp;   $admin->add\_cap( 'edit\_others\_saw\_courses' );

&nbsp;   $admin->add\_cap( 'delete\_others\_saw\_courses' );

&nbsp;   

&nbsp;   $instructor = get\_role( 'saw\_lms\_instructor' );

&nbsp;   $instructor->add\_cap( 'edit\_saw\_courses' );

&nbsp;   $instructor->add\_cap( 'delete\_saw\_courses' );

&nbsp;   // Instructor NEMÁ 'edit\_others\_saw\_courses'

}

register\_activation\_hook( \_\_FILE\_\_, 'saw\_lms\_add\_capabilities' );

```



\### ❌ NIKDY NEDĚLEJ

```php

// ❌ ŠPATNĚ - Žádná kontrola oprávnění

if ( isset( $\_POST\['delete\_course'] ) ) {

&nbsp;   wp\_delete\_post( $\_POST\['course\_id'] );

}



// ❌ ŠPATNĚ - Kontrola jen admin, ne specific capability

if ( is\_admin() ) {

&nbsp;   // Toto může projít i pro logged-in usera v admin area!

}



// ❌ ŠPATNĚ - Předpoklad, že uživatel má právo editovat cizí content

if ( current\_user\_can( 'edit\_saw\_courses' ) ) {

&nbsp;   // Měl bys také zkontrolovat ownership!

}

```



---



\## ⏱️ Rate Limiting



\### PRAVIDLO #7: Chraň API endpointy a kritické akce



\*\*Rate limiting prevencí:\*\*

\- Brute force útoky

\- API abuse

\- DDoS

\- Automatizované skripty



\### Implementace (WordPress Transients)

```php

// ✅ Rate limit helper funkce

function saw\_lms\_rate\_limit\_check( $action, $max\_attempts = 5, $period = 300 ) {

&nbsp;   $user\_id = get\_current\_user\_id();

&nbsp;   $ip = saw\_lms\_get\_client\_ip();

&nbsp;   

&nbsp;   // Unique key pro tento user/IP a akci

&nbsp;   $key = 'saw\_lms\_rl\_' . $action . '\_' . ( $user\_id ? $user\_id : $ip );

&nbsp;   

&nbsp;   // Získat počet pokusů

&nbsp;   $attempts = get\_transient( $key );

&nbsp;   

&nbsp;   if ( false === $attempts ) {

&nbsp;       $attempts = 0;

&nbsp;   }

&nbsp;   

&nbsp;   // Překročen limit?

&nbsp;   if ( $attempts >= $max\_attempts ) {

&nbsp;       return new WP\_Error(

&nbsp;           'rate\_limit\_exceeded',

&nbsp;           sprintf(

&nbsp;               \_\_( 'Too many attempts. Please try again in %d minutes.', 'saw-lms' ),

&nbsp;               ceil( $period / 60 )

&nbsp;           )

&nbsp;       );

&nbsp;   }

&nbsp;   

&nbsp;   // Inkrementuj a nastav expiraci

&nbsp;   set\_transient( $key, $attempts + 1, $period );

&nbsp;   

&nbsp;   return true;

}



// ✅ Získání IP adresy

function saw\_lms\_get\_client\_ip() {

&nbsp;   $ip = '';

&nbsp;   

&nbsp;   if ( ! empty( $\_SERVER\['HTTP\_CLIENT\_IP'] ) ) {

&nbsp;       $ip = $\_SERVER\['HTTP\_CLIENT\_IP'];

&nbsp;   } elseif ( ! empty( $\_SERVER\['HTTP\_X\_FORWARDED\_FOR'] ) ) {

&nbsp;       $ip = $\_SERVER\['HTTP\_X\_FORWARDED\_FOR'];

&nbsp;   } else {

&nbsp;       $ip = $\_SERVER\['REMOTE\_ADDR'];

&nbsp;   }

&nbsp;   

&nbsp;   return filter\_var( $ip, FILTER\_VALIDATE\_IP ) ? $ip : '0.0.0.0';

}

```



\### Použití v Endpointech

```php

// ✅ Login rate limiting

add\_action( 'wp\_login\_failed', 'saw\_lms\_login\_failed' );



function saw\_lms\_login\_failed( $username ) {

&nbsp;   $limit\_check = saw\_lms\_rate\_limit\_check( 'login\_failed', 5, 900 ); // 5 pokusů za 15 minut

&nbsp;   

&nbsp;   if ( is\_wp\_error( $limit\_check ) ) {

&nbsp;       // Příliš mnoho pokusů - loguj to

&nbsp;       SAW\_LMS\_Logger::error( 'Login rate limit exceeded', array(

&nbsp;           'username' => $username,

&nbsp;           'ip'       => saw\_lms\_get\_client\_ip(),

&nbsp;       ) );

&nbsp;   }

}



// ✅ API endpoint rate limiting

add\_action( 'rest\_api\_init', function() {

&nbsp;   register\_rest\_route( 'saw-lms/v1', '/verify-certificate', array(

&nbsp;       'methods'             => 'POST',

&nbsp;       'callback'            => 'saw\_lms\_verify\_certificate',

&nbsp;       'permission\_callback' => '\_\_return\_true', // Veřejný endpoint

&nbsp;   ) );

} );



function saw\_lms\_verify\_certificate( $request ) {

&nbsp;   // Rate limit - 10 ověření za 5 minut

&nbsp;   $limit\_check = saw\_lms\_rate\_limit\_check( 'verify\_certificate', 10, 300 );

&nbsp;   

&nbsp;   if ( is\_wp\_error( $limit\_check ) ) {

&nbsp;       return new WP\_Error(

&nbsp;           'rate\_limit\_exceeded',

&nbsp;           $limit\_check->get\_error\_message(),

&nbsp;           array( 'status' => 429 )

&nbsp;       );

&nbsp;   }

&nbsp;   

&nbsp;   // Process verification...

}



// ✅ AJAX action rate limiting

add\_action( 'wp\_ajax\_nopriv\_saw\_lms\_contact\_instructor', 'saw\_lms\_contact\_instructor' );



function saw\_lms\_contact\_instructor() {

&nbsp;   // Rate limit - 3 zprávy za 10 minut

&nbsp;   $limit\_check = saw\_lms\_rate\_limit\_check( 'contact\_instructor', 3, 600 );

&nbsp;   

&nbsp;   if ( is\_wp\_error( $limit\_check ) ) {

&nbsp;       wp\_send\_json\_error( $limit\_check->get\_error\_message() );

&nbsp;   }

&nbsp;   

&nbsp;   // Send message...

}

```



\### Redis Rate Limiting (Pokročilé)

```php

// ✅ Redis-based rate limiting (vyšší výkon)

function saw\_lms\_redis\_rate\_limit( $action, $max\_attempts = 5, $period = 300 ) {

&nbsp;   if ( ! class\_exists( 'Redis' ) ) {

&nbsp;       return saw\_lms\_rate\_limit\_check( $action, $max\_attempts, $period ); // Fallback

&nbsp;   }

&nbsp;   

&nbsp;   $redis = new Redis();

&nbsp;   $redis->connect( '127.0.0.1', 6379 );

&nbsp;   

&nbsp;   $user\_id = get\_current\_user\_id();

&nbsp;   $ip = saw\_lms\_get\_client\_ip();

&nbsp;   $key = 'saw\_lms\_rl:' . $action . ':' . ( $user\_id ? $user\_id : $ip );

&nbsp;   

&nbsp;   $current = $redis->get( $key );

&nbsp;   

&nbsp;   if ( false === $current ) {

&nbsp;       $redis->setex( $key, $period, 1 );

&nbsp;       return true;

&nbsp;   }

&nbsp;   

&nbsp;   if ( $current >= $max\_attempts ) {

&nbsp;       return new WP\_Error( 'rate\_limit\_exceeded', 'Too many attempts' );

&nbsp;   }

&nbsp;   

&nbsp;   $redis->incr( $key );

&nbsp;   return true;

}

```



\### Best Practices



\- \*\*Login/Register:\*\* 5 pokusů za 15 minut

\- \*\*API (veřejné):\*\* 10-20 requestů za minutu

\- \*\*API (autentizované):\*\* 60-100 requestů za minutu

\- \*\*Kontakt formuláře:\*\* 3 zprávy za 10 minut

\- \*\*Certifikát verifikace:\*\* 10 ověření za 5 minut



---



\## 📤 Bezpečné Nahrávání Souborů



\### PRAVIDLO #8: Nikdy nedůvěřuj uploadovaným souborům



\*\*Hrozby:\*\*

\- Nahrání PHP backdoor skriptu

\- Nesprávný MIME type (fake extension)

\- Příliš velké soubory (DoS)

\- Škodlivý obsah (XSS v SVG)



\### WordPress Upload Handler

```php

// ✅ Whitelist povolených typů

add\_filter( 'upload\_mimes', 'saw\_lms\_custom\_upload\_mimes' );



function saw\_lms\_custom\_upload\_mimes( $mimes ) {

&nbsp;   // Přidej vlastní typy (opatrně!)

&nbsp;   $mimes\['pdf']  = 'application/pdf';

&nbsp;   $mimes\['mp4']  = 'video/mp4';

&nbsp;   $mimes\['webm'] = 'video/webm';

&nbsp;   

&nbsp;   // Zakáž nebezpečné typy

&nbsp;   unset( $mimes\['exe'] );

&nbsp;   unset( $mimes\['php'] );

&nbsp;   unset( $mimes\['phtml'] );

&nbsp;   unset( $mimes\['phps'] );

&nbsp;   

&nbsp;   return $mimes;

}



// ✅ Validace uploadu

function saw\_lms\_handle\_file\_upload( $file ) {

&nbsp;   // Check if file was uploaded

&nbsp;   if ( ! isset( $file\['tmp\_name'] ) || ! is\_uploaded\_file( $file\['tmp\_name'] ) ) {

&nbsp;       return new WP\_Error( 'invalid\_upload', 'File upload failed' );

&nbsp;   }

&nbsp;   

&nbsp;   // Check file size (např. max 50MB)

&nbsp;   $max\_size = 50 \* 1024 \* 1024; // 50MB

&nbsp;   if ( $file\['size'] > $max\_size ) {

&nbsp;       return new WP\_Error( 'file\_too\_large', 'File exceeds 50MB limit' );

&nbsp;   }

&nbsp;   

&nbsp;   // Validate MIME type

&nbsp;   $allowed\_types = array(

&nbsp;       'application/pdf',

&nbsp;       'video/mp4',

&nbsp;       'video/webm',

&nbsp;       'image/jpeg',

&nbsp;       'image/png',

&nbsp;   );

&nbsp;   

&nbsp;   $finfo = finfo\_open( FILEINFO\_MIME\_TYPE );

&nbsp;   $mime = finfo\_file( $finfo, $file\['tmp\_name'] );

&nbsp;   finfo\_close( $finfo );

&nbsp;   

&nbsp;   if ( ! in\_array( $mime, $allowed\_types, true ) ) {

&nbsp;       return new WP\_Error( 'invalid\_file\_type', 'File type not allowed' );

&nbsp;   }

&nbsp;   

&nbsp;   // Sanitize filename

&nbsp;   $filename = sanitize\_file\_name( $file\['name'] );

&nbsp;   

&nbsp;   // Upload pomocí WordPress

&nbsp;   $upload = wp\_handle\_upload( $file, array(

&nbsp;       'test\_form' => false,

&nbsp;       'mimes'     => array(

&nbsp;           'pdf'  => 'application/pdf',

&nbsp;           'mp4'  => 'video/mp4',

&nbsp;           'webm' => 'video/webm',

&nbsp;           'jpg'  => 'image/jpeg',

&nbsp;           'png'  => 'image/png',

&nbsp;       ),

&nbsp;   ) );

&nbsp;   

&nbsp;   if ( isset( $upload\['error'] ) ) {

&nbsp;       return new WP\_Error( 'upload\_error', $upload\['error'] );

&nbsp;   }

&nbsp;   

&nbsp;   return $upload; // array( 'file' => path, 'url' => url, 'type' => mime )

}

```



\### Bezpečné Umístění

```php

// ✅ Upload do zabezpečeného adresáře

function saw\_lms\_get\_secure\_upload\_dir() {

&nbsp;   $upload\_dir = wp\_upload\_dir();

&nbsp;   $saw\_lms\_dir = $upload\_dir\['basedir'] . '/saw-lms-files';

&nbsp;   

&nbsp;   // Vytvoř adresář, pokud neexistuje

&nbsp;   if ( ! file\_exists( $saw\_lms\_dir ) ) {

&nbsp;       wp\_mkdir\_p( $saw\_lms\_dir );

&nbsp;       

&nbsp;       // Přidej .htaccess pro ochranu

&nbsp;       $htaccess\_content = "Options -Indexes\\n";

&nbsp;       $htaccess\_content .= "<FilesMatch '\\.(php|phtml|php3|php4|php5|pl|py|jsp|asp|html|htm|shtml|sh|cgi)$'>\\n";

&nbsp;       $htaccess\_content .= "  Require all denied\\n";

&nbsp;       $htaccess\_content .= "</FilesMatch>\\n";

&nbsp;       

&nbsp;       file\_put\_contents( $saw\_lms\_dir . '/.htaccess', $htaccess\_content );

&nbsp;       

&nbsp;       // Přidej index.php (prázdný soubor)

&nbsp;       file\_put\_contents( $saw\_lms\_dir . '/index.php', '<?php // Silence is golden' );

&nbsp;   }

&nbsp;   

&nbsp;   return $saw\_lms\_dir;

}

```



\### Certifikáty (Generované PDF)

```php

// ✅ Bezpečné generování PDF certifikátů

function saw\_lms\_generate\_certificate\_pdf( $user\_id, $course\_id ) {

&nbsp;   require\_once SAW\_LMS\_PLUGIN\_DIR . 'vendor/autoload.php';

&nbsp;   

&nbsp;   $mpdf = new \\Mpdf\\Mpdf();

&nbsp;   

&nbsp;   // Escapuj všechna data před použitím v PDF

&nbsp;   $user = get\_userdata( $user\_id );

&nbsp;   $course = get\_post( $course\_id );

&nbsp;   

&nbsp;   $html = '<h1>' . esc\_html( $user->display\_name ) . '</h1>';

&nbsp;   $html .= '<p>' . esc\_html( $course->post\_title ) . '</p>';

&nbsp;   

&nbsp;   $mpdf->WriteHTML( $html );

&nbsp;   

&nbsp;   // Uložit do zabezpečeného adresáře

&nbsp;   $cert\_dir = saw\_lms\_get\_secure\_upload\_dir() . '/certificates';

&nbsp;   wp\_mkdir\_p( $cert\_dir );

&nbsp;   

&nbsp;   // Unikátní název souboru

&nbsp;   $filename = 'cert\_' . $user\_id . '\_' . $course\_id . '\_' . time() . '.pdf';

&nbsp;   $filepath = $cert\_dir . '/' . $filename;

&nbsp;   

&nbsp;   $mpdf->Output( $filepath, 'F' );

&nbsp;   

&nbsp;   return $filepath;

}



// ✅ Zabezpečené stahování certifikátu

add\_action( 'template\_redirect', 'saw\_lms\_download\_certificate' );



function saw\_lms\_download\_certificate() {

&nbsp;   if ( ! isset( $\_GET\['download\_certificate'] ) ) {

&nbsp;       return;

&nbsp;   }

&nbsp;   

&nbsp;   $cert\_id = absint( $\_GET\['download\_certificate'] );

&nbsp;   

&nbsp;   // Verify nonce

&nbsp;   if ( ! isset( $\_GET\['nonce'] ) || ! wp\_verify\_nonce( $\_GET\['nonce'], 'download\_cert\_' . $cert\_id ) ) {

&nbsp;       wp\_die( 'Security check failed' );

&nbsp;   }

&nbsp;   

&nbsp;   // Verify ownership

&nbsp;   $cert = saw\_lms\_get\_certificate( $cert\_id );

&nbsp;   if ( ! $cert || $cert->user\_id != get\_current\_user\_id() ) {

&nbsp;       wp\_die( 'Access denied' );

&nbsp;   }

&nbsp;   

&nbsp;   $filepath = $cert->file\_path;

&nbsp;   

&nbsp;   if ( ! file\_exists( $filepath ) ) {

&nbsp;       wp\_die( 'File not found' );

&nbsp;   }

&nbsp;   

&nbsp;   // Force download

&nbsp;   header( 'Content-Type: application/pdf' );

&nbsp;   header( 'Content-Disposition: attachment; filename="' . basename( $filepath ) . '"' );

&nbsp;   header( 'Content-Length: ' . filesize( $filepath ) );

&nbsp;   readfile( $filepath );

&nbsp;   exit;

}

```



\### ❌ NIKDY NEDĚLEJ

```php

// ❌ ŠPATNĚ - Důvěra v extension

$ext = pathinfo( $\_FILES\['file']\['name'], PATHINFO\_EXTENSION );

if ( 'jpg' === $ext ) {

&nbsp;   move\_uploaded\_file( $\_FILES\['file']\['tmp\_name'], $dest );

}



// ❌ ŠPATNĚ - Přímý přístup k uploaded souborům přes URL

// uploads/saw-lms-files/malicious.php ← spustitelné!



// ❌ ŠPATNĚ - Žádná validace MIME typu

move\_uploaded\_file( $\_FILES\['file']\['tmp\_name'], $dest );

```



---



\## ✅ Security Checklist



\### Pre-Commit Checklist



Před každým commitem projdi tento checklist:



\#### \*\*1. Input Validation\*\*

\- \[ ] Všechny `$\_GET`, `$\_POST`, `$\_REQUEST` jsou sanitizovány?

\- \[ ] Všechny integer hodnoty používají `absint()` nebo `intval()`?

\- \[ ] Všechny URL používají `esc\_url\_raw()`?

\- \[ ] Všechny email adresy používají `sanitize\_email()`?

\- \[ ] JSON data jsou validována pomocí `json\_decode()` + `json\_last\_error()`?



\#### \*\*2. Output Escaping\*\*

\- \[ ] Všechny `echo` a `print` mají `esc\_html()`, `esc\_attr()`, nebo `esc\_url()`?

\- \[ ] JavaScript strings jsou escapovány pomocí `esc\_js()`?

\- \[ ] HTML content používá `wp\_kses\_post()` nebo vlastní whitelist?

\- \[ ] Žádné raw výstupy bez escapování?



\#### \*\*3. CSRF Protection\*\*

\- \[ ] Všechny formuláře mají `wp\_nonce\_field()`?

\- \[ ] Všechny AJAX akce ověřují nonce pomocí `wp\_verify\_nonce()` nebo `check\_ajax\_referer()`?

\- \[ ] REST API endpointy mají `permission\_callback`?



\#### \*\*4. SQL Injection\*\*

\- \[ ] Všechny SQL dotazy používají `$wpdb->prepare()`?

\- \[ ] Žádné string concatenation v SQL?

\- \[ ] IN clauses používají placeholders?

\- \[ ] LIKE queries používají `$wpdb->esc\_like()`?



\#### \*\*5. Capability Checks\*\*

\- \[ ] Všechny admin akce mají `current\_user\_can()` check?

\- \[ ] AJAX callbacks ověřují oprávnění?

\- \[ ] REST API endpointy mají správný `permission\_callback`?

\- \[ ] Delete/Edit operace ověřují ownership?



\#### \*\*6. File Uploads\*\*

\- \[ ] Whitelist povolených MIME typů?

\- \[ ] Validace skutečného MIME typu (ne jen extension)?

\- \[ ] Velikost souboru limitována?

\- \[ ] Soubory ukládány mimo webroot nebo s .htaccess ochranou?

\- \[ ] Filename sanitizován pomocí `sanitize\_file\_name()`?



\#### \*\*7. Rate Limiting\*\*

\- \[ ] Veřejné API endpointy mají rate limiting?

\- \[ ] Login/Register akce jsou limitovány?

\- \[ ] Kontakt formuláře mají rate limiting?



\#### \*\*8. Error Handling\*\*

\- \[ ] Žádné `var\_dump()`, `print\_r()`, `die()` v kódu?

\- \[ ] Chyby logované pomocí `SAW\_LMS\_Logger::error()`?

\- \[ ] Uživatelské chybové zprávy neobjasňují interní logiku?

\- \[ ] WP\_DEBUG = false v produkci?



\#### \*\*9. Sensitive Data\*\*

\- \[ ] API klíče a tokeny nejsou hardcodované?

\- \[ ] Hesla nikdy neukládána v plain textu?

\- \[ ] Citlivá data šifrována?

\- \[ ] Žádné credentials v Git commitu?



\#### \*\*10. Code Quality\*\*

\- \[ ] `composer phpcs` prošlo bez chyb?

\- \[ ] `composer phpstan` prošlo bez varování?

\- \[ ] Žádné TODO/FIXME poznámky související se security?



---



\### Code Review Checklist



Při review kódu kolegy:



\#### \*\*High Priority\*\*

\- \[ ] Nonce verification na místě?

\- \[ ] Capability checks přítomny?

\- \[ ] SQL dotazy používají prepared statements?

\- \[ ] Vstupy sanitizovány, výstupy escapovány?

\- \[ ] File upload validace správně implementována?



\#### \*\*Medium Priority\*\*

\- \[ ] Rate limiting na public endpoints?

\- \[ ] Error handling bez odhalení citlivých dat?

\- \[ ] Logging bezpečnostních událostí?

\- \[ ] Žádné hardcoded credentials?



\#### \*\*Low Priority\*\*

\- \[ ] Komentáře vysvětlují security logiku?

\- \[ ] PHPDoc obsahuje @param a @return typy?

\- \[ ] Kód následuje WordPress Coding Standards?



---



\### Penetration Testing Checklist



Pravidelně (před velkými releases) testuj:



\#### \*\*Authentication \& Authorization\*\*

\- \[ ] Zkus obejít login rate limiting

\- \[ ] Zkus přistoupit k admin stránkám bez přihlášení

\- \[ ] Zkus editovat cizí kurzy/lekce

\- \[ ] Zkus změnit oprávnění jiného uživatele



\#### \*\*Input Validation\*\*

\- \[ ] SQL injection (zkus `' OR '1'='1`)

\- \[ ] XSS (`<script>alert('XSS')</script>`)

\- \[ ] Path traversal (`../../wp-config.php`)

\- \[ ] Command injection (`;ls -la`)



\#### \*\*Session Management\*\*

\- \[ ] Session hijacking

\- \[ ] Session fixation

\- \[ ] Cookie security (httponly, secure flags)



\#### \*\*File Uploads\*\*

\- \[ ] Nahrání PHP souboru

\- \[ ] Dvojitá extension (`file.php.jpg`)

\- \[ ] Příliš velký soubor

\- \[ ] Nesprávný MIME type



\#### \*\*API Endpoints\*\*

\- \[ ] Obejití rate limiting

\- \[ ] CSRF na public endpoints

\- \[ ] Information disclosure

\- \[ ] Parameter tampering



---



\## 🚨 Security Incident Response



\### Co dělat při objevení bezpečnostní chyby?



1\. \*\*OKAMŽITĚ:\*\*

&nbsp;  - Zaloguj problém pomocí `SAW\_LMS\_Logger::critical()`

&nbsp;  - Deaktivuj postižený endpoint/feature (pokud možné)

&nbsp;  - Informuj tým



2\. \*\*DO 1 HODINY:\*\*

&nbsp;  - Assess dopadu (kolik uživatelů ovlivněno?)

&nbsp;  - Vytvoř hotfix branch

&nbsp;  - Implementuj opravu

&nbsp;  - Testuj opravu



3\. \*\*DO 4 HODIN:\*\*

&nbsp;  - Deploy hotfix na produkci

&nbsp;  - Notify ovlivněné uživatele (pokud nutné)

&nbsp;  - Update SECURITY.md s lessons learned



4\. \*\*DO 24 HODIN:\*\*

&nbsp;  - Post-mortem analýza

&nbsp;  - Aktualizace security checklistů

&nbsp;  - Team meeting pro prevenci podobných problémů



---



\## 📚 Dodatečné Zdroje



\### WordPress Security Resources

\- \[WordPress VIP Security Best Practices](https://docs.wpvip.com/technical-references/security/)

\- \[WordPress Codex: Data Validation](https://codex.wordpress.org/Data\_Validation)

\- \[WordPress Codex: Securing Your WordPress Site](https://wordpress.org/support/article/hardening-wordpress/)



\### OWASP Resources

\- \[OWASP Top 10 (2021)](https://owasp.org/Top10/)

\- \[OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/)

\- \[OWASP WordPress Security Guide](https://owasp.org/www-project-wordpress-security/)



\### PHP Security

\- \[PHP Security Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/PHP\_Configuration\_Cheat\_Sheet.html)

\- \[Secure Coding in PHP](https://phptherightway.com/#security)



---



\## 📝 Závěr



\*\*Bezpečnost není jednorázová akce, ale kontinuální proces.\*\*



\- ✅ \*\*Vždy validuj vstupy\*\*

\- ✅ \*\*Vždy escapuj výstupy\*\*

\- ✅ \*\*Vždy používej nonces\*\*

\- ✅ \*\*Vždy používej prepared statements\*\*

\- ✅ \*\*Vždy ověřuj oprávnění\*\*

\- ✅ \*\*Vždy loguj security events\*\*

\- ✅ \*\*Vždy testuj před nasazením\*\*



\*\*Při jakýchkoliv pochybách - ptej se, konzultuj, a raději buď opatrnější!\*\*



---



\*\*Verze:\*\* 1.0  

\*\*Poslední update:\*\* 2025-10-23  

\*\*Responsible:\*\* SAW Development Team  

\*\*Contact:\*\* \[security@saw-lms.com](mailto:security@saw-lms.com)

