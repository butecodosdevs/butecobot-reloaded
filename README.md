# Buteco Bot Reloaded

This bot was developed using:
- [Laracord](https://laracord.com/)
- [DiscordPHP](https://github.com/discord-php/DiscordPHP)

# Getting started

**Dependencias necessarias para instalação do bot**

# **PHP** 
Na construção desse bot foi utilizado o PHP 8.3.6 opte por essa verção ou superior.active

# **Composer** 
O Composer é um gerenciador de dependências para PHP.

# **Docker** 
Docker é uma plataforma de software de código aberto que usa o contêiner para automatizar o ambiente de desenvolvimento, implantação e execução de aplicativos.

Instalação das dependencias 

<details>
  <summary>Windows</summary>

  Comece a ver esse video.

  [Instalando Todas as dependencias](https://www.youtube.com/watch?v=cURBfTEKBYE) 
  
</details>

<details>
  <summary>Linux</summary>

  Instale o PHP de preferência usando o gerenciador de verções eu recomento o ASDF

  [ASDF](https://github.com/asdf-vm/asdf) 

  [PHP](https://github.com/asdf-community/asdf-php) 

  A docker pode variar de acordo com o sua distro, mas aqui abaixo o link para configurar no ubunto (não é uma recomentação odeio ubunto)

  [Docker](https://docs.docker.com/engine/install/ubuntu/) 

</details>

# **Configuração do Discord**

Crie sua conta no discord ou utilize a sua pessoal, acesse a area de develops do discord

[Discord](https://discord.com/developers/applications)

Crie uma nova aplicação 
![alt text](/BotInstallation/NewApplication.png)

![alt text](/BotInstallation/BotApplication.png)

Resete o token e copie 
![alt text](/BotInstallation/Token.png)
Cole esse token no arquivo .env **DISCORD_TOKEN**

Habilite as permissões necessárias do bot

![alt text](/BotInstallation/PermissionsBot.png)


# **Instalação do bot**

Clone o repositório
```bash
git clone https://github.com/butecodosdevs/butecobot-reloaded
```

Entre na pasta do bot e execute o comando abaixo
```bash
composer install
```

Execute o comando abaixo para iniciar o bot
```bash
make dev-essentials
```

# **Adicionando bot no Discord**
De o nome para o seu Servidor a sua preferência

![alt text](/BotInstallation/NewServer.png)

Volte para o site do discord Develop

![alt text](/BotInstallation/IdClient.png)

Entre no site para gerar o link

[Discord Link Api](https://discordapi.com/permissions.html#8) 

![alt text](/BotInstallation/LinkApi.png)

So selecionar o servido que você criou e continuar ate instalar o bot

![alt text](/BotInstallation/InstallBot.png)


Pronto so entrar na pasta do bot e iniciar o bot
```bash
php laracord
```
So testar e desenvolver novas features

![alt text](/BotInstallation/TestBot.png)


