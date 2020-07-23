# How

## Setup

Copy the .env_example to .env so docker-compose can source it's vars,
afterwards populate the stand-in "lorem" with actual secure and unique strings

```
cp .env_example .env
```


## Run

```bash
docker-compose up -d --build --force-recreate --remove-orphans
```

## Logs

```bash
docker-compose logs -f
```
