FROM node:20-alpine

WORKDIR /app

# Installer les dépendances depuis le package.json
COPY frontend/package*.json ./
RUN npm ci

# Copier le code source (surchargé par le volume en dev)
COPY frontend/ .

EXPOSE 3000

CMD ["npm", "run", "dev"]
