FROM node:22-alpine 

WORKDIR /app/frontend

COPY frontend/package*.json ./

RUN npm ci

COPY frontend ./

CMD ["npm", "run", "build", "--", "--configuration", "production"]

