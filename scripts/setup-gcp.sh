#!/bin/bash
# setup-gcp.sh - Script de configuración automática de Google Cloud Platform
# Para Literatura Regional App

echo "🚀 Configurando Google Cloud Platform para Literatura Regional App"
echo "=================================================================="
echo ""

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Variables
PROJECT_ID="literatura-regional-puebla"
REGION="us-central1"
ZONE="us-central1-a"

# Verificar que gcloud está instalado
if ! command -v gcloud &> /dev/null
then
    echo -e "${RED}❌ gcloud CLI no está instalado${NC}"
    echo "Instala desde: https://cloud.google.com/sdk/docs/install"
    exit 1
fi

echo -e "${GREEN}✅ gcloud CLI encontrado${NC}"

# Login a Google Cloud
echo ""
echo "📝 Iniciando sesión en Google Cloud..."
gcloud auth login

# Configurar proyecto
echo ""
echo "🏗️  Configurando proyecto: $PROJECT_ID"
gcloud config set project $PROJECT_ID

# Verificar que el proyecto existe
if ! gcloud projects describe $PROJECT_ID &> /dev/null; then
    echo -e "${YELLOW}⚠️  El proyecto no existe. Creándolo...${NC}"
    gcloud projects create $PROJECT_ID --name="Literatura Regional Puebla"
fi

# Configurar región y zona por defecto
gcloud config set compute/region $REGION
gcloud config set compute/zone $ZONE

echo -e "${GREEN}✅ Proyecto configurado${NC}"

# Habilitar APIs necesarias
echo ""
echo "🔌 Habilitando APIs de Google Cloud..."

APIS=(
    "firestore.googleapis.com"
    "firebase.googleapis.com"
    "cloudfunctions.googleapis.com"
    "storage-api.googleapis.com"
    "cloudscheduler.googleapis.com"
    "cloudtasks.googleapis.com"
    "cloudmessaging.googleapis.com"
    "vision.googleapis.com"
    "language.googleapis.com"
    "aiplatform.googleapis.com"
)

for api in "${APIS[@]}"
do
    echo "  Habilitando $api..."
    gcloud services enable $api --quiet
done

echo -e "${GREEN}✅ APIs habilitadas${NC}"

# Crear bucket de Cloud Storage
echo ""
echo "📦 Creando bucket de Cloud Storage..."

BUCKET_NAME="${PROJECT_ID}-storage"
gsutil mb -l $REGION gs://$BUCKET_NAME 2>/dev/null || echo "Bucket ya existe"
gsutil mb -l $REGION gs://${PROJECT_ID}-backups 2>/dev/null || echo "Bucket de backups ya existe"
gsutil mb -l $REGION gs://${PROJECT_ID}-apk 2>/dev/null || echo "Bucket de APK ya existe"

echo -e "${GREEN}✅ Buckets creados${NC}"

# Crear base de datos Firestore
echo ""
echo "💾 Configurando Firestore..."

gcloud firestore databases create \
    --location=$REGION \
    --type=firestore-native \
    --quiet 2>/dev/null || echo "Firestore ya está creado"

echo -e "${GREEN}✅ Firestore configurado${NC}"

# Configurar service account
echo ""
echo "🔐 Configurando Service Account..."

SERVICE_ACCOUNT_NAME="literatura-regional-sa"
SERVICE_ACCOUNT_EMAIL="${SERVICE_ACCOUNT_NAME}@${PROJECT_ID}.iam.gserviceaccount.com"

gcloud iam service-accounts create $SERVICE_ACCOUNT_NAME \
    --display-name="Literatura Regional Service Account" \
    --quiet 2>/dev/null || echo "Service Account ya existe"

# Asignar roles
gcloud projects add-iam-policy-binding $PROJECT_ID \
    --member="serviceAccount:${SERVICE_ACCOUNT_EMAIL}" \
    --role="roles/firebase.admin" \
    --quiet

gcloud projects add-iam-policy-binding $PROJECT_ID \
    --member="serviceAccount:${SERVICE_ACCOUNT_EMAIL}" \
    --role="roles/datastore.user" \
    --quiet

# Crear key del service account
echo "Creando key del service account..."
gcloud iam service-accounts keys create service-account-key.json \
    --iam-account=$SERVICE_ACCOUNT_EMAIL \
    --quiet 2>/dev/null || echo "Key ya existe"

echo -e "${GREEN}✅ Service Account configurado${NC}"

# Resumen
echo ""
echo "=================================================================="
echo -e "${GREEN}🎉 Configuración de GCP completada exitosamente!${NC}"
echo "=================================================================="
echo ""
echo "📋 Resumen:"
echo "  • Proyecto: $PROJECT_ID"
echo "  • Región: $REGION"
echo "  • Firestore: Configurado"
echo "  • Cloud Storage: 3 buckets creados"
echo "  • Service Account: $SERVICE_ACCOUNT_EMAIL"
echo ""
echo "🔑 Archivo generado:"
echo "  • service-account-key.json (¡Guárdalo en lugar seguro!)"
echo ""
echo "📍 Próximos pasos:"
echo "  1. Ve a console.firebase.google.com"
echo "  2. Agrega el proyecto existente: $PROJECT_ID"
echo "  3. Habilita Authentication → Email/Password"
echo "  4. Descarga google-services.json para Android"
echo "  5. Descarga GoogleService-Info.plist para iOS"
echo ""
echo "  Después ejecuta: node setup-firestore.js"
echo ""
