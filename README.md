# Project Winnest

Project Winnest — our Project Innovate at NHL Stenden.

## Repository structure

```
Project-Winnest/
├── Database/         # SQL file(s) for the database schema and data
├── Documentation/     # Project documentation
├── Figma Designs/     # UI/UX design files
├── Presentations/      # Project presentations
├── Website/           # Website source code (PHP, HTML, CSS)
└── README.md
```

## Prerequisites

- [Docker](https://www.docker.com/products/docker-desktop/) installed and running, with the project's Docker setup already configured
- [Git](https://git-scm.com/) (to clone the repository. Clone inside the Docker public folder)

## 2. Start Docker

Open Docker (Docker Desktop), then start the containers:

## 3. Import the database

1. Go to the phpMyAdmin link for the **NHLStenden** container.
2. Log in.
3. Go to the **Import** tab.
4. Import the database file from the `Database` folder.

## 4. Open the website

1. Go to `localhost` in your browser.
2. Open the project folder.
3. Open **Website**.
4. Open **dashboard**.

All the "Add" functions on the dashboard page work, **except Add Race Result** (`add-race-result.php`) — currently broken, known issue.
The Youngster page also works.
