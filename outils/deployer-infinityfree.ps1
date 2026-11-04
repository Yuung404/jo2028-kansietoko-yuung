<#
.SYNOPSIS
    Déploie le site JO 2028 sur InfinityFree par FTP chiffré (FTPS), avec curl.

.DESCRIPTION
    1. Demande le mot de passe du compte d'hébergement au clavier (saisie masquée,
       jamais affichée ni enregistrée sur le disque de façon durable).
    2. Décompresse l'archive du site dans un dossier temporaire et y génère le fichier
       database/config.php de production (debug désactivé).
    3. Envoie tous les fichiers dans /htdocs en FTPS avec curl.exe (fourni avec Windows 10 et 11).
       curl remplace FtpWebRequest de .NET : avec le serveur Pure-FTPd d'InfinityFree, FtpWebRequest
       ferme mal la session TLS du canal de données et chaque envoi échoue en erreur 451.
    4. Supprime la page par défaut d'InfinityFree (index2.html), puis tous les fichiers temporaires.

.EXAMPLE
    powershell -ExecutionPolicy Bypass -File deployer-infinityfree.ps1
    powershell -ExecutionPolicy Bypass -File deployer-infinityfree.ps1 -Simulation
#>
param(
    [string]$Archive          = (Join-Path $PSScriptRoot 'jo2028-site.zip'),
    [string]$FtpHote          = 'ftpupload.net',
    [string]$FtpUtilisateur   = 'if0_43336494',
    [string]$DossierDistant   = '/htdocs',
    [string]$MysqlHote        = 'sql311.infinityfree.com',
    [string]$MysqlBase        = 'if0_43336494_jo2028',
    [string]$MysqlUtilisateur = 'if0_43336494',
    [switch]$Simulation       # prépare tout sans rien envoyer (vérification du script)
)

$ErrorActionPreference = 'Stop'
$temp    = Join-Path $env:TEMP 'jo2028-deploiement'
$curlCfg = Join-Path $env:TEMP 'jo2028-curl.cfg'    # contient le mot de passe : supprimé dans le bloc finally
$utf8    = [System.Text.UTF8Encoding]::new($false)  # UTF-8 sans BOM

# Chaîne entre guillemets pour le fichier de configuration de curl : \ et " sont échappés
function Cfg([string]$valeur) { '"' + $valeur.Replace('\', '\\').Replace('"', '\"') + '"' }

try {
    if (-not (Test-Path $Archive)) { throw "Archive introuvable : $Archive" }
    if (-not $Simulation -and -not (Get-Command curl.exe -ErrorAction SilentlyContinue)) {
        throw 'curl.exe introuvable (il est fourni avec Windows 10 version 1803 et suivantes).'
    }

    # --- 1. Mot de passe (saisie masquée)
    if ($Simulation) {
        $motDePasse = 'simulation'
    } else {
        $saisie = Read-Host "Mot de passe du compte d'hébergement $FtpUtilisateur (saisie masquée)" -AsSecureString
        $motDePasse = [System.Net.NetworkCredential]::new('', $saisie).Password
        if ([string]::IsNullOrEmpty($motDePasse)) { throw 'Aucun mot de passe saisi.' }
    }

    # --- 2. Préparation locale : archive + config.php de production
    Remove-Item -Recurse -Force $temp -ErrorAction SilentlyContinue
    Expand-Archive -Path $Archive -DestinationPath $temp
    $mdpPhp = $motDePasse.Replace('\', '\\').Replace("'", "\'")     # chaîne PHP entre apostrophes
    $config = @"
<?php
/** Configuration de PRODUCTION (InfinityFree) : générée par deployer-infinityfree.ps1. */
return [
    'host'     => '$MysqlHote',
    'port'     => 3306,
    'dbname'   => '$MysqlBase',
    'user'     => '$MysqlUtilisateur',
    'password' => '$mdpPhp',
    'debug'    => false,
];
"@
    # UTF-8 SANS BOM : un BOM serait envoyé avant les en-têtes HTTP et casserait les sessions
    [System.IO.File]::WriteAllText((Join-Path $temp 'database\config.php'), $config, $utf8)

    $fichiers = @(Get-ChildItem $temp -Recurse -File -Force)
    Write-Host "Préparé : $($fichiers.Count) fichiers (config.php de production inclus)."

    # --- 3. Fichier de configuration de curl : identifiants + une paire fichier / adresse par envoi
    $lignes = [System.Collections.Generic.List[string]]::new()
    $lignes.Add('user = ' + (Cfg "${FtpUtilisateur}:$motDePasse"))
    $lignes.Add('ssl-reqd')            # FTPS obligatoire : refuse de continuer sans chiffrement
    $lignes.Add('ftp-create-dirs')     # crée les dossiers distants manquants
    $lignes.Add('ftp-pasv')
    $lignes.Add('silent')
    $lignes.Add('show-error')
    $lignes.Add('retry = 3')
    $lignes.Add('connect-timeout = 30')
    $lignes.Add('write-out = "  OK : %{url_effective}\n"')   # texte ASCII : la console Windows affiche mal les accents de curl
    foreach ($f in $fichiers) {
        $relatif = $f.FullName.Substring($temp.Length + 1).Replace('\', '/')
        $lignes.Add('upload-file = ' + (Cfg $f.FullName))
        $lignes.Add('url = ' + (Cfg "ftp://$FtpHote$DossierDistant/$relatif"))
    }
    [System.IO.File]::WriteAllLines($curlCfg, $lignes, $utf8)

    if ($Simulation) {
        Write-Host '--- SIMULATION : aucun envoi. Fichiers qui seraient envoyés :'
        $fichiers | ForEach-Object { '  ' + $DossierDistant + '/' + $_.FullName.Substring($temp.Length + 1).Replace('\', '/') }
        $ligne = (Get-Content (Join-Path $temp 'database\config.php') -Encoding UTF8 | Select-String 'debug').Line
        Write-Host "config.php (extrait) : $($ligne.Trim())"
        $bom = [System.IO.File]::ReadAllBytes((Join-Path $temp 'database\config.php'))[0..2] -join ','
        Write-Host "3 premiers octets de config.php : $bom (60,63,112 = '<?p' : pas de BOM)"
        Write-Host "Configuration curl : $($lignes.Count) lignes, $($fichiers.Count) paires fichier / adresse."
        return
    }

    # --- 4. Envoi : un seul appel à curl, la connexion FTPS est réutilisée d'un fichier à l'autre
    Write-Host "Envoi de $($fichiers.Count) fichiers en FTPS vers $FtpHote$DossierDistant ..."
    & curl.exe --config $curlCfg
    if ($LASTEXITCODE -ne 0) {
        throw "Échec de l'envoi (code curl $LASTEXITCODE). Code 67 : mot de passe refusé ; code 64 : chiffrement refusé ; code 28 : délai dépassé."
    }
    Write-Host "$($fichiers.Count) fichiers envoyés dans $DossierDistant." -ForegroundColor Green

    # --- 5. Page par défaut d'InfinityFree (absente au deuxième déploiement : erreur ignorée)
    $authCfg = @('user = ' + (Cfg "${FtpUtilisateur}:$motDePasse"), 'ssl-reqd', 'silent', 'no-progress-meter')
    [System.IO.File]::WriteAllLines($curlCfg, $authCfg, $utf8)
    & curl.exe --config $curlCfg --quote "DELE $DossierDistant/index2.html" --output NUL "ftp://$FtpHote/"
    if ($LASTEXITCODE -eq 0) { Write-Host 'Page par défaut index2.html supprimée.' }

    Write-Host 'Déploiement terminé.' -ForegroundColor Green
}
finally {
    # Le dossier temporaire (config.php) et la configuration curl contiennent le mot de passe : toujours supprimés
    Remove-Item -Recurse -Force $temp -ErrorAction SilentlyContinue
    Remove-Item -Force $curlCfg -ErrorAction SilentlyContinue
    $motDePasse = $null; $mdpPhp = $null; $config = $null; $lignes = $null; $authCfg = $null
}
