# Новостная аналитика (News Analytics Dashboard)

## Overview
A pure static HTML news aggregation dashboard that pulls from 16 RSS news sources across Russia, Central Asia, and international outlets. The app runs entirely in the browser with no backend.

## Features
- 16 RSS news sources (CNN, TechCrunch, Bloomberg, BBC, Reuters, The Guardian, РИА Новости, РБК, Meduza, and more)
- Automatic translation of English articles to Russian via MyMemory API
- Topic categorization: Politics, War/Military, Cybersecurity, Incidents, Economy
- 7-day news archive stored in localStorage
- Auto-refresh every 30 minutes
- Daily digest export (DOC format) at 21:00 MSK
- Date-based archive browsing

## Architecture
- **Frontend only**: Pure HTML/CSS/JavaScript in `index.html`
- **No build system**: No npm, no bundler, no dependencies
- **No backend**: All data fetched client-side from external RSS feeds via proxy (allorigins.win)

## Running
The app is served using Python's built-in HTTP server:
```
python3 -m http.server 5000 --bind 0.0.0.0
```

## Deployment
Configured as a **static** deployment - files served directly from the project root.

## Project Structure
```
index.html    - The entire application (HTML + CSS + JS)
```
