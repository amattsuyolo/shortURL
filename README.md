# Short URL System

This Laravel project is a URL shortener that generates and manages short URLs.

---

## Features

- Customizable URL generation (random, hash-based, Base62).
- Efficient short code management with Bloom Filter.
- Unit tests for core functionality.

---

## Setup Instructions

1. **Clone the repository:**
   ```bash
   git clone https://github.com/amattsuyolo/shortURL.git
   cd shortURL
   ```

2. **Start Laravel Sail:**
   ```bash
   ./vendor/bin/sail up -d
   ```

3. **Install dependencies:**
   ```bash
   ./vendor/bin/sail composer install
   ```

4. **Set up environment:**
   ```bash
   cp .env.example .env
   ./vendor/bin/sail artisan key:generate
   ```

5. **Run migrations:**
   ```bash
   ./vendor/bin/sail artisan migrate
   ```

6. **Access the application:**
   Open `http://localhost` in your browser.

---

## Routes

- `GET /`: Displays URL creation form.
- `POST /short-url`: Generates short URLs.
- `GET /{shortUrl}`: Redirects to the original URL.

---

## Testing

Run unit tests:
```bash
./vendor/bin/sail artisan test
```

---

## Related Articles

Learn more about the design and concepts behind this project:

1. [Backend Essentials: URL Shortener and Rate Limiting - Part 1](https://medium.com/@mattsuyolo/%E5%BE%8C%E7%AB%AF%E5%BF%85%E6%9C%83%E9%A1%8C-%E7%9F%AD%E7%B6%B2%E5%9D%80%E8%88%87%E9%99%90%E6%B5%81-1-%E9%99%90%E6%B5%81%E8%A8%AD%E8%A8%88-bacf96699617)

2. [Backend Essentials: URL Shortener and Caching - Part 2](https://medium.com/@mattsuyolo/%E5%BE%8C%E7%AB%AF%E5%BF%85%E6%9C%83%E9%A1%8C-%E7%9F%AD%E7%B6%B2%E5%9D%80%E8%88%87%E9%99%90%E6%B5%81-2-%E5%BF%AB%E5%8F%96%E8%A8%AD%E8%A8%88-561212c69ee5)

3. [Backend Essentials: URL Shortener Generation - Part 3](https://medium.com/@mattsuyolo/%E5%BE%8C%E7%AB%AF%E5%BF%85%E6%9C%83%E9%A1%8C-%E7%9F%AD%E7%B6%B2%E5%9D%80-3-%E7%94%9F%E6%88%90%E7%9F%AD%E7%B6%B2%E5%9D%80-3bf0c3384e6a)

---

