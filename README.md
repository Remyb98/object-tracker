# Object tracker

**Object tracker** is a PHP **trait** that allows you to track changes in a class.  
It captures the initial state of an object and detects modifications on properties annotated with the `#[Track]` attribute.

## Features
m
- Track changes in object properties
- Supports PHP 8+ attributes
- Use aliases or a display property for objects
- `commit()` function to update the snapshot

## Installation

Install via Composer:
```bash
composer require your-namespace/trackable
```

## Usage

### Declare a class

Use the Trackable trait in your class:

```php
<?php

use App\Trait\Trackable;
use App\Attribute\Track;

class User
{
    use Trackable;

    #[Track]
    private string $name;

    #[Track(alias: "user_email")]
    private string $email;

    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
    }
}
```

### Take a snapshot

```php
<?php

$user = new User("Alice", "alice@example.com");
$user->snapshot();

### Detect changes

<?php

$user->name = "Bob";

$changes = $user->getChanges();
/*
$changes = [
    'name' => [
        'before' => 'Alice',
        'after' => 'Bob',
    ]
];
*/
```

### Commit changes

```php
<?php

$user->commit(); // Updates the snapshot with the current state
````
## Advanced Usage

You can track **object properties** and display a specific attribute instead of the full object.

```php

<?php

use App\Trait\Trackable;
use App\Attribute\Track;

class Address
{
    public string $street;
    public string $city;

    public function __construct(string $street, string $city)
    {
        $this->street = $street;
        $this->city = $city;
    }
}

class User
{
    use Trackable;

    #[Track(display: "city")]
    private Address $address;

    public function __construct(Address $address)
    {
        $this->address = $address;
    }
}

$address = new Address("123 Main St", "Paris");
$user = new User($address);
$user->snapshot();

$address->city = "Lyon";

$changes = $user->getChanges();
/*
$changes = [
    'address' => [
        'before' => 'Paris',
        'after' => 'Lyon'
    ]
];
*/
```

Notes:

- The display option in `#[Track]` tells Trackable which property of the object to show instead of the object itself.  
- If the object has a `__toString()` method, it will be used automatically.  
- Otherwise, the class name will be displayed in square brackets (e.g., `[Address]`).
