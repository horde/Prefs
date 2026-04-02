# horde/prefs Usage Guide

## Overview

horde/prefs is a preferences management library that provides abstraction over various storage backends (SQL, LDAP, Mongo, File, etc.). 
It is designed for **managed preferences** where preferences are pre-defined in code or configuration files, not for arbitrary key-value storage.

## Key Concepts

### 1. Preferences Must Be Pre-Defined

**CRITICAL**: You cannot arbitrarily set new preferences at store time.
Preferences must be defined upfront in code.
Horde Framework applications use a `config/prefs.php` configuration file.
You can also setup your preferences in a bootstrap class.

```php
// WRONG - This will fail silently
$prefs = new Horde_Prefs('myapp', $storage);
$prefs['new_pref'] = 'value';  // Returns false - pref doesn't exist
$prefs->store();               // Nothing is saved

// GOOD! - Define preferences first in config/prefs.php
// config/prefs.php:
$_prefs['new_pref'] = [
    'value' => 'default_value',
    'type' => 'text',
];

// Then use in application:
$prefs = new Horde_Prefs('myapp', $storage);
$prefs['new_pref'] = 'custom_value';  // Works - pref exists
$prefs->store();                       // Saves to backend
```

### 2. How Preferences Are Loaded

When you create a `Horde_Prefs` object, preferences are loaded in this order:

1. **Schema setup** Code (or in horde apps, a prefs.php file) defines which preferences exist and what their default values are.
2. **Storage backend** (SQL/LDAP/etc) loads user's saved values
3. **Scope object** merges defaults with saved values

```php
// Application loads prefs definitions from config/prefs.php
$registry->loadConfigFile('prefs.php', ['_prefs'], 'myapp');

// Create Prefs object with storage backend
$storage = new Horde_Prefs_Storage_Sql($user, ['db' => $db]);
$prefs = new Horde_Prefs('myapp', $storage);

// Under the hood:
// 1. Creates Horde_Prefs_Scope for 'myapp'
// 2. Calls $storage->get($scope) to load saved values
// 3. Only prefs defined in config/prefs.php can be retrieved
```

## Basic Usage Examples

### Reading Preferences

```php
<?php
use Horde_Prefs;
use Horde_Prefs_Storage_Sql;

// Create storage backend
$storage = new Horde_Prefs_Storage_Sql('username', [
    'db' => $db_adapter
]);

// Create prefs object for a scope (usually app name)
$prefs = new Horde_Prefs('myapp', $storage);

// Read preferences (uses getValue() internally)
$theme = $prefs->getValue('theme');
$theme = $prefs['theme'];  // ArrayAccess shortcut

// Check if pref exists
if ($prefs->exists('theme')) {
    $theme = $prefs['theme'];
}

// Check if pref is using default value
if ($prefs->isDefault('theme')) {
    echo "User hasn't customized theme";
}

// Check if pref is locked (read-only)
if ($prefs->isLocked('theme')) {
    echo "User cannot change theme";
}
```

### Modifying Preferences

```php
<?php
// Modify existing preference
$prefs->setValue('theme', 'dark');
$prefs['theme'] = 'dark';  // ArrayAccess shortcut

// Changes are stored immediately on shutdown (via register_shutdown_function)
// Or call store() manually:
$prefs->store();

// Store without shutdown handler:
$prefs->store(false);  // Don't rely on shutdown
```

### Working with Multiple Scopes

```php
<?php
// Preferences are organized by scope (typically application name)
$horde_prefs = new Horde_Prefs('horde', $storage);
$imp_prefs = new Horde_Prefs('imp', $storage);

// Each scope has its own set of preferences
$horde_prefs['theme'] = 'dark';   // horde.theme
$imp_prefs['preview'] = true;     // imp.preview

// Change scope dynamically
$prefs = new Horde_Prefs('horde', $storage);
$prefs['theme'] = 'dark';
$prefs->changeScope('imp');
$prefs['preview'] = true;
```

## Storage Backends

### SQL Storage

```php
<?php
use Horde_Prefs_Storage_Sql;
use Horde_Db_Adapter_Pdo_Mysql;

// Create database adapter
$db = new Horde_Db_Adapter_Pdo_Mysql([
    'hostspec' => 'localhost',
    'username' => 'dbuser',
    'password' => 'dbpass',
    'database' => 'horde',
]);

// Create SQL storage
$storage = new Horde_Prefs_Storage_Sql('username', [
    'db' => $db,
    'table' => 'horde_prefs',  // Optional, default: horde_prefs
]);

$prefs = new Horde_Prefs('myapp', $storage);
```

**Database Schema:**

See migration/ folder

### Multiple Storage Backends (Cascading)

```php
<?php
// Use multiple backends - reads from all, writes to first writable
$storage = [
    new Horde_Prefs_Storage_Sql($user, ['db' => $db]),    // Primary
    new Horde_Prefs_Storage_Ldap($user, ['ldap' => $ldap]), // Fallback
];

$prefs = new Horde_Prefs('myapp', $storage);

// When reading: checks SQL first, then LDAP
// When writing: writes to SQL (first backend)
```

### Null Storage (No Persistence)

```php
<?php
use Horde_Prefs_Storage_Null;

// For testing or when persistence isn't needed
$storage = new Horde_Prefs_Storage_Null('username');
$prefs = new Horde_Prefs('myapp', $storage);

// Preferences work in memory but are never saved
```

## Advanced Features

### Locked Preferences

```php
<?php
// In config/prefs.php:
$_prefs['theme'] = [
    'value' => 'corporate',
    'locked' => true,  // User cannot change
];

// In application:
$prefs['theme'] = 'dark';  // setValue() returns false
echo $prefs['theme'];      // Still 'corporate'
```

### Default Values

```php
<?php
// Get default value for a preference
$default = $prefs->getDefault('theme');

// Check if current value is default
if ($prefs->isDefault('theme')) {
    echo "Using default theme";
}

// Reset to default
$prefs->remove('theme');  // Removes custom value, reverts to default
```

### Preference Removal

```php
<?php
// Remove single preference (reverts to default)
$prefs->remove('theme');

// Remove all preferences in current scope
$prefs->remove();  // Removes all myapp.* prefs

// Remove from storage backend
foreach ($storage as $backend) {
    $backend->remove('myapp', 'theme');  // Remove specific pref
    $backend->remove('myapp');           // Remove all in scope
}
```

## Integration with Horde Applications

In a full Horde application context, preferences are loaded automatically:

```php
<?php
// In Horde applications, prefs are available globally
global $prefs;

// Prefs are automatically loaded from:
// 1. horde/config/prefs.php (base preferences)
// 2. myapp/config/prefs.php (application preferences)
// 3. Backend storage (user's saved values)

// Just use them:
$theme = $prefs['theme'];
$prefs['theme'] = 'dark';

// The Factory handles all the complexity:
// $prefs = $injector->getInstance('Horde_Core_Factory_Prefs')
//     ->create('myapp');
```

## Common Pitfalls

### 1. Trying to Set Undefined Preferences

```php
// WRONG - Fails silently
$prefs['undefined_pref'] = 'value';  // Returns false

// GOOD! - Define in config/prefs.php first
// Then use in application code
```

### 2. Not Calling store()

```php
// WRONG - Changes might not be saved
$prefs['theme'] = 'dark';
// Exit without calling store() - relies on shutdown handler

// GOOD! - Explicitly store
$prefs['theme'] = 'dark';
$prefs->store();
```

### 3. Wrong Storage User

```php
// WRONG - Storage user doesn't match prefs scope
$storage = new Horde_Prefs_Storage_Sql('user1', $params);
$prefs = new Horde_Prefs('app', $storage, ['user' => 'user2']);
// Loads user2's prefs but saves to user1!

// GOOD! - Keep user consistent
$user = 'username';
$storage = new Horde_Prefs_Storage_Sql($user, $params);
$prefs = new Horde_Prefs('app', $storage, ['user' => $user]);
```

## See Also

- `horde/base/config/prefs.php` - Complete preference definition example
- `horde/imp/config/prefs.php` - IMP mail client preferences
- `Horde_Core_Factory_Prefs` - Factory for creating prefs in Horde apps
- `Horde_Core_Prefs_Ui` - UI layer for preference management
