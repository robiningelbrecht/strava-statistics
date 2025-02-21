<p align="center">
  <img src="public/assets/images/logo.svg" width="250" alt="Logo" >
</p>

<h1 align="center">Strava Statistics</h1>

<p align="center">
<a href="https://github.com/artop123/strava-statistics/actions/workflows/ci.yml"><img src="https://github.com/artop123/strava-statistics/actions/workflows/ci.yml/badge.svg" alt="CI"></a>
<a href="https://github.com/artop123/strava-statistics/actions/workflows/docker-image.yml"><img src="https://github.com/artop123/strava-statistics/actions/workflows/docker-image.yml/badge.svg" alt="Publish Docker image"></a>
<a href="https://hub.docker.com/r/artop/strava-statistics"><img src="https://img.shields.io/docker/image-size/artop/strava-statistics" alt="Docker Image Size"></a>
<a href="https://hub.docker.com/r/artop/strava-statistics"><img src="https://img.shields.io/docker/pulls/artop/strava-statistics" alt="Docker pulls"></a>
</p>

---

<h4 align="center">Strava Statistics is a self-hosted web app designed to provide you with better stats.</h4>

<p align="center">
  <a href="https://github.com/robiningelbrecht/strava-statistics">View the original project for installation instructions and more details</a>
</p>

## What is different

* FTP is now calculated automatically from your activities
* FTP chart has been replaced with eFTP, showing FTP values for running and cycling
* Each activity now displays its calculated eFTP
* Activity intensity is determined based on an activity type-specific eFTP

## Instructions

You will need to recalculate power in old activities that have already been imported. This can be done by 

```
docker compose exec app bin/console app:strava:recalculate
```

This can take up to 10 minutes depending on the number of activities.