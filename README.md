<p align="center">
    <img src="public/assets/images/strava.png"
         alt="Strava">
</p>

<p align="center">
<a href="https://github.com/robiningelbrecht/strava-statistics/actions/workflows/ci.yml"><img src="https://github.com/robiningelbrecht/strava-statistics/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
<a href="https://github.com/robiningelbrecht/strava-statistics/actions/workflows/docker-image.yml"><img src="https://github.com/robiningelbrecht/strava-statistics/actions/workflows/docker-image.yml/badge.svg" alt="Publish Docker image"></a>
<a href="https://raw.githubusercontent.com/robiningelbrecht/strava-statistics/refs/heads/master/LICENSE"><img src="https://img.shields.io/github/license/robiningelbrecht/strava-statistics?color=428f7e&logo=open%20source%20initiative&logoColor=white" alt="License"></a>
<a href="https://hub.docker.com/r/robiningelbrecht/strava-statistics"><img src="https://img.shields.io/docker/image-size/robiningelbrecht/strava-statistics" alt="Docker Image Size"></a>
<a href="https://hub.docker.com/r/robiningelbrecht/strava-statistics"><img src="https://img.shields.io/docker/pulls/robiningelbrecht/strava-statistics" alt="Docker pulls"></a>
<a href="https://hub.docker.com/r/robiningelbrecht/strava-statistics"><img src="https://img.shields.io/docker/v/robiningelbrecht/strava-statistics?sort=semver" alt="Docker version"></a>
</p>

---

Strava Statistics is a self-hosted web app designed to provide you with better stats

## 📸 Showcase

https://github.com/user-attachments/assets/7fd9262d-744f-44dc-9c17-6730b1cfc383

## ⚠️ Disclaimer

* 🛠️ __Under active development__: Expect frequent updates, bugs, and breaking changes.
* 📦 __Backup before updates__: Always backup your Docker volumes before upgrading.
* 🔄 __Stay up-to-date__: Make sure you're running the latest version for the best experience.

## 🪄 Prerequisites

You'll need a `Strava client ID`, `Strava client Secret` and a `refresh token`

* Navigate to your [Strava API settings page](https://www.strava.com/settings/api).
  Copy the `client ID` and `client secret`
* Next you need to obtain a `Strava API refresh token`. 
    * Navigate to https://developers.strava.com/docs/getting-started/#d-how-to-authenticate
      and scroll down to "_For demonstration purposes only, here is how to reproduce the graph above with cURL:_"
    * Follow the 11 steps explained there
    * Make sure you set the `scope` in step 2 to `activity:read_all` to make sure your refresh token has access to all activities

## 🛠️ Installation 

Start off by showing some ❤️ and give this repo a star ;)

### docker-compose.yml

```yml
services:
  app:
    image: robiningelbrecht/strava-statistics:latest
    volumes:
      - ./build:/var/www/build
      - ./storage/database:/var/www/storage/database
      - ./storage/files:/var/www/storage/files
    env_file: ./.env
    ports:
      - 8080:8080
```

### .env

```bash
# The client id of your Strava app.
STRAVA_CLIENT_ID=YOUR_CLIENT_ID
# The client secret of your Strava app.
STRAVA_CLIENT_SECRET=YOUR_CLIENT_SECRET
# The refresh of your Strava app.
STRAVA_REFRESH_TOKEN=YOUR_REFRESH_TOKEN

# Allowed options: metric or imperial
UNIT_SYSTEM=metric
# Activity types to import. Allowed options: "Ride", "VirtualRide" and "Run"
ACTIVITY_TYPES_TO_IMPORT='["Ride", "VirtualRide"]'
# Your birthday. Needed to calculate heart rate zones.
ATHLETE_BIRTHDAY=YYYY-MM-DD
# History of weight (in kg or pounds, depending on UNIT_SYSTEM). Needed to calculate relative w/kg.
# Check https://github.com/robiningelbrecht/strava-statistics/wiki for more info.
ATHLETE_WEIGHTS='{
    "YYYY-MM-DD": 74.6,
    "YYYY-MM-DD": 70.3
}'
# History of FTP. Needed to calculate activity stress level.
# Check https://github.com/robiningelbrecht/strava-statistics/wiki for more info.
FTP_VALUES='{
    "YYYY-MM-DD": 198,
    "YYYY-MM-DD": 220
}'
```

### Import all challenges and trophies

Strava does not allow to fetch all your completed challenges and trophies, but there's a little workaround if you'd like to import those:
* Navigate to https://www.strava.com/athletes/[YOUR_ATHLETE_ID]/trophy-case
* Open the page's source code and copy everything
  ![Trophy case source code](public/assets/images/readme/trophy-case-source-code.png)
* Make sure you save the source code to the file `./storage/files/strava-challenge-history.html`
* On the next import, all your challenges will be imported

## ⚡️Import and build statistics

```bash
docker compose exec app bin/console app:strava:import-data
docker compose exec app bin/console app:strava:build-files
```

## ⏰ Periodic imports

You can configure a crontab on your host system:

```bash
0 18 * * * docker compose exec app bin/console app:strava:import-data && 
docker compose exec app bin/console app:strava:build-files
```

## 📚 Wiki

Read [the wiki](https://github.com/robiningelbrecht/strava-statistics/wiki) before opening new issues. The question you have might be answered over there.

## 🧐 Some things to consider

* Because of technical (Strava) limitations, not all Strava challenges can be imported. Only the visible ones on your public profile can be imported
  (please be sure that your profile is public, otherwise this won't work)
* Running the import for the first time can take a while, depending on how many activities you have on Strava.
  Strava's API has a `rate limit` of `100 request per 15 minutes` and a `1000 requests per day`. We have to make sure
  this limit is not exceeded. See https://developers.strava.com/docs/rate-limits/. If you have more than 500 activities,
  you might run into the daily rate limit. If you do so, the app will import the remaining activities the next day(s).
* You can only build the files once all data from Strava was imported

## 💡 Feature request?

For any feedback, help or feature requests, please [open a new issue](https://github.com/robiningelbrecht/strava-statictics/issues/new)

## 💻 Local development

If you want to add features or fix bugs yourself, you can do this by setting up the project on your local machine.
Just clone this git repository and you should be good to go.

The project can be run in a single `Docker` container which uses PHP.
There's also a `Make` file to... make things easier:

```bash
# Run a docker-compose command.
make dc cmd="run"

# Run "composer" command in the php-cli container.
make composer arg="install"

# Run an app console command
make console arg="app:some:command"

# Run the test suite.
make phpunit

# Run PHPStan
make phpstan
```

For other useful `Make` commands, check [Makefile](Makefile)
