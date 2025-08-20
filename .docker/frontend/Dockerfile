FROM node:20

WORKDIR /var/www/html

# Copy frontend package.json first for caching
COPY package*.json ./

RUN npm install

# Copy all frontend files (assuming root contains frontend code)
COPY . .

EXPOSE 5173

CMD ["npm", "run", "dev", "--", "--host"]
