# ---- Base : dépendances installées une seule fois ----
FROM node:20.19.2-alpine AS base
WORKDIR /app
COPY frontend/package*.json ./
RUN npm ci

# ---- Dev : serveur Vite avec live-reload (docker-compose.yml, volume monté) ----
FROM base AS dev
COPY frontend/ .
EXPOSE 3000
CMD ["npm", "run", "dev"]

# ---- Build : compilation statique pour la prod ----
FROM base AS build
COPY frontend/ .
# "" => appels API relatifs (/api/...), routés par le reverse proxy nginx.
# Ne pas coder l'IP/domaine en dur : ça resterait valide même si le domaine change.
ARG VITE_API_URL=""
ENV VITE_API_URL=$VITE_API_URL
RUN npm run build

# ---- Prod : fichiers statiques servis par nginx ----
FROM nginx:1.27-alpine AS prod
COPY --from=build /app/dist /usr/share/nginx/html
COPY docker/react/nginx.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
