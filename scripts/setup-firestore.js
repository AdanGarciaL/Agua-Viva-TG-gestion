// setup-firestore.js - Poblar Firestore con datos iniciales
// Para Literatura Regional App

const admin = require('firebase-admin');
const serviceAccount = require('./service-account-key.json');

// Inicializar Firebase Admin
admin.initializeApp({
  credential: admin.credential.cert(serviceAccount),
  databaseURL: `https://literatura-regional-puebla.firebaseio.com`
});

const db = admin.firestore();
const auth = admin.auth();

// Los 13 grupos de Puebla
const GRUPOS = [
  { id: 'AMALUCAN', nombre: 'Amalucan' },
  { id: 'APIZACO', nombre: 'Apizaco' },
  { id: 'BUENAVISTA', nombre: 'Buenavista' },
  { id: 'GUADALUPE_HIDALGO', nombre: 'Guadalupe Hidalgo' },
  { id: 'LOMAS_DEL_SUR', nombre: 'Lomas del Sur' },
  { id: 'SAN_BALTAZAR', nombre: 'San Baltazar' },
  { id: 'SAN_FELIPE', nombre: 'San Felipe' },
  { id: 'TLAXCALA', nombre: 'Tlaxcala' },
  { id: 'CHOLULA', nombre: 'Cholula' },
  { id: 'ZACATELCO', nombre: 'Zacatelco' },
  { id: 'SANTA_ANA', nombre: 'Santa Ana' },
  { id: 'AMOZOC', nombre: 'Amozoc' },
  { id: 'HUAMANTLA', nombre: 'Huamantla' }
];

// Función para crear grupos en Firestore
async function crearGrupos() {
  console.log('📦 Creando 13 grupos en Firestore...\n');
  
  const batch = db.batch();
  
  for (const grupo of GRUPOS) {
    const grupoRef = db.collection('grupos').doc(grupo.id);
    
    batch.set(grupoRef, {
      id: grupo.id,
      nombre: grupo.nombre,
      tipo: 'servidor',
      region: 'puebla',
      
      ubicacion: {
        ciudad: grupo.nombre,
        direccion: '',
        referencias: '',
        coordenadas: { lat: 0, lng: 0 }
      },
      
      contacto: {
        telefono: '',
        email: `${grupo.id.toLowerCase()}@aguaviva.org`
      },
      
      administradoras: [],
      
      config: {
        permitir_fiados: true,
        limite_fiado_maximo: 1000.00,
        horario_apertura: '09:00',
        horario_cierre: '18:00'
      },
      
      activo: true,
      creado_en: admin.firestore.FieldValue.serverTimestamp()
    });
    
    console.log(`  ✅ ${grupo.nombre}`);
  }
  
  await batch.commit();
  console.log('\n🎉 13 grupos creados exitosamente\n');
}

// Función para crear grupo Regional
async function crearGrupoRegional() {
  console.log('🏛️  Creando grupo Regional...\n');
  
  await db.collection('grupos').doc('REGIONAL').set({
    id: 'REGIONAL',
    nombre: 'Literatura Regional Puebla',
    tipo: 'regional',
    region: 'puebla',
    
    ubicacion: {
      ciudad: 'Puebla',
      direccion: '',
      referencias: 'Oficina Central',
      coordenadas: { lat: 0, lng: 0 }
    },
    
    contacto: {
      telefono: '',
      email: 'regional@aguaviva.org'
    },
    
    administradoras: [],
    activo: true,
    creado_en: admin.firestore.FieldValue.serverTimestamp()
  });
  
  console.log('  ✅ Literatura Regional Puebla\n');
}

// Función para crear primera usuaria Regional Admin
async function crearUsuariaRegional() {
  console.log('👤 Creando primera usuaria Regional Admin...\n');
  
  const email = 'regional@aguaviva.org';
  const password = 'Temporal123!';
  const nombreCompleto = 'Administradora Regional';
  
  try {
    // Crear usuario en Firebase Authentication
    const userRecord = await auth.createUser({
      email: email,
      password: password,
      displayName: nombreCompleto,
      emailVerified: true
    });
    
    console.log(`  ✅ Usuario creado en Authentication: ${userRecord.uid}`);
    
    // Crear documento en Firestore
    await db.collection('usuarios').doc(userRecord.uid).set({
      uid: userRecord.uid,
      nombre_completo: nombreCompleto,
      email: email,
      
      grupo_id: 'REGIONAL',
      grupo_nombre: 'Literatura Regional Puebla',
      
      role: 'regional_admin',
      
      activa: true,
      password_temporal: true,
      
      creada_por: 'system',
      creada_en: admin.firestore.FieldValue.serverTimestamp(),
      modificada_en: admin.firestore.FieldValue.serverTimestamp(),
      ultima_conexion: null,
      
      telefono: '',
      foto_perfil: null
    });
    
    console.log(`  ✅ Documento creado en Firestore`);
    
    // Asignar custom claim de rol
    await auth.setCustomUserClaims(userRecord.uid, {
      role: 'regional_admin',
      grupo_id: 'REGIONAL'
    });
    
    console.log(`  ✅ Custom claims asignados`);
    
    console.log('\n📧 Credenciales de la usuaria Regional:');
    console.log('  ╔════════════════════════════════════════╗');
    console.log(`  ║ Email:    ${email.padEnd(26)} ║`);
    console.log(`  ║ Password: ${password.padEnd(26)} ║`);
    console.log('  ╚════════════════════════════════════════╝');
    console.log('  ⚠️  La usuaria DEBE cambiar esta password en el primer login\n');
    
  } catch (error) {
    if (error.code === 'auth/email-already-exists') {
      console.log('  ⚠️  Usuario ya existe, obteniendo datos...');
      const userRecord = await auth.getUserByEmail(email);
      console.log(`  ℹ️  UID: ${userRecord.uid}`);
    } else {
      throw error;
    }
  }
}

// Función para crear usuarias de ejemplo (opcional)
async function crearUsuariosEjemplo() {
  console.log('👥 Creando usuarios de ejemplo para testing...\n');
  
  const usuariosEjemplo = [
    {
      nombre: 'Admin Cholula',
      email: 'admin.cholula@aguaviva.org',
      grupo: 'CHOLULA',
      role: 'server_admin'
    },
    {
      nombre: 'Vendedora Cholula',
      email: 'venta.cholula@aguaviva.org',
      grupo: 'CHOLULA',
      role: 'server_worker'
    },
    {
      nombre: 'Admin Amalucan',
      email: 'admin.amalucan@aguaviva.org',
      grupo: 'AMALUCAN',
      role: 'server_admin'
    }
  ];
  
  for (const usuario of usuariosEjemplo) {
    try {
      const password = 'Temporal123!';
      
      const userRecord = await auth.createUser({
        email: usuario.email,
        password: password,
        displayName: usuario.nombre,
        emailVerified: true
      });
      
      await db.collection('usuarios').doc(userRecord.uid).set({
        uid: userRecord.uid,
        nombre_completo: usuario.nombre,
        email: usuario.email,
        grupo_id: usuario.grupo,
        grupo_nombre: GRUPOS.find(g => g.id === usuario.grupo)?.nombre || '',
        role: usuario.role,
        activa: true,
        password_temporal: true,
        creada_por: 'system',
        creada_en: admin.firestore.FieldValue.serverTimestamp(),
        modificada_en: admin.firestore.FieldValue.serverTimestamp(),
        ultima_conexion: null,
        telefono: '',
        foto_perfil: null
      });
      
      await auth.setCustomUserClaims(userRecord.uid, {
        role: usuario.role,
        grupo_id: usuario.grupo
      });
      
      console.log(`  ✅ ${usuario.nombre} (${usuario.email})`);
      
    } catch (error) {
      if (error.code === 'auth/email-already-exists') {
        console.log(`  ⚠️  ${usuario.email} ya existe`);
      } else {
        console.error(`  ❌ Error creando ${usuario.email}:`, error.message);
      }
    }
  }
  
  console.log('\n');
}

// Función para crear configuración global
async function crearConfiguracion() {
  console.log('⚙️  Creando configuración global...\n');
  
  await db.collection('configuracion').doc('app').set({
    tema: {
      color_primario: '#0d47a1',
      color_secundario: '#1976d2',
      color_acento: '#42a5f5',
      modo_oscuro: false
    },
    
    region: 'puebla',
    moneda: 'MXN',
    
    reglas: {
      stock_minimo_alerta: 10,
      dias_vencimiento_pedido: 7,
      porcentaje_descuento_servidor: 33
    },
    
    version_minima_requerida: '1.0.0',
    version_actual: '1.0.0',
    
    actualizado_en: admin.firestore.FieldValue.serverTimestamp()
  });
  
  console.log('  ✅ Configuración global creada\n');
}

// Función para crear libros de ejemplo
async function crearLibrosEjemplo() {
  console.log('📚 Creando libros de ejemplo...\n');
  
  const librosEjemplo = [
    {
      titulo: 'Agua Viva - Paso 1',
      autor: 'Editorial Hortelano',
      isbn: '9781234567890',
      categoria: 'literatura_aprobada',
      editorial: 'Hortelano',
      precio_venta: 120.00,
      precio_distribucion: 80.00,
      stock_regional: 500
    },
    {
      titulo: 'Agua Viva - Paso 2',
      autor: 'Editorial Hortelano',
      isbn: '9781234567891',
      categoria: 'literatura_aprobada',
      editorial: 'Hortelano',
      precio_venta: 120.00,
      precio_distribucion: 80.00,
      stock_regional: 400
    },
    {
      titulo: 'Sobriedad Emocional',
      autor: 'AA World Services',
      isbn: '9781234567892',
      categoria: 'literatura_aprobada',
      editorial: 'AA',
      precio_venta: 80.00,
      precio_distribucion: 50.00,
      stock_regional: 300
    },
    {
      titulo: 'Llavero Agua Viva',
      autor: 'N/A',
      categoria: 'souvenirs',
      editorial: 'Agua Viva',
      precio_venta: 30.00,
      precio_distribucion: 20.00,
      stock_regional: 1000
    }
  ];
  
  const batch = db.batch();
  
  for (const libro of librosEjemplo) {
    const libroRef = db.collection('inventario_regional').doc();
    
    batch.set(libroRef, {
      id: libroRef.id,
      tipo: libro.categoria === 'souvenirs' ? 'souvenir' : 'libro',
      
      titulo: libro.titulo,
      autor: libro.autor,
      isbn: libro.isbn || null,
      codigo_barras: null,
      
      categoria: libro.categoria,
      editorial: libro.editorial,
      idioma: 'es',
      
      precio_venta: libro.precio_venta,
      precio_distribucion: libro.precio_distribucion,
      stock_regional: libro.stock_regional,
      stock_minimo: 50,
      
      foto_portada: null,
      descripcion: '',
      paginas: null,
      
      aprobado_por_grupo: true,
      aprobado_por_editorial: true,
      activo: true,
      
      creado_por: 'system',
      creado_en: admin.firestore.FieldValue.serverTimestamp(),
      actualizado_en: admin.firestore.FieldValue.serverTimestamp()
    });
    
    console.log(`  ✅ ${libro.titulo}`);
  }
  
  await batch.commit();
  console.log('\n');
}

// Función principal
async function main() {
  console.log('\n');
  console.log('═══════════════════════════════════════════════════════════');
  console.log('  📚 Setup Firestore - Literatura Regional Puebla');
  console.log('═══════════════════════════════════════════════════════════');
  console.log('\n');
  
  try {
    // 1. Crear grupos
    await crearGrupos();
    await crearGrupoRegional();
    
    // 2. Crear usuarias
    await crearUsuariaRegional();
    await crearUsuariosEjemplo();
    
    // 3. Crear configuración
    await crearConfiguracion();
    
    // 4. Crear libros de ejemplo
    await crearLibrosEjemplo();
    
    console.log('═══════════════════════════════════════════════════════════');
    console.log('  🎉 Setup completado exitosamente!');
    console.log('═══════════════════════════════════════════════════════════');
    console.log('\n');
    console.log('📋 Resumen:');
    console.log('  • 13 grupos creados');
    console.log('  • 1 grupo regional');
    console.log('  • 1 usuaria regional admin');
    console.log('  • 3 usuarios de ejemplo (testing)');
    console.log('  • 4 libros de ejemplo');
    console.log('  • Configuración global');
    console.log('\n');
    console.log('🔐 Credenciales de acceso:');
    console.log('  Email:    regional@aguaviva.org');
    console.log('  Password: Temporal123!');
    console.log('\n');
    console.log('📍 Próximos pasos:');
    console.log('  1. Descarga google-services.json de Firebase Console');
    console.log('  2. Configura la app React Native');
    console.log('  3. Prueba el login con las credenciales de arriba');
    console.log('\n');
    
  } catch (error) {
    console.error('\n❌ Error durante el setup:', error);
    process.exit(1);
  }
  
  process.exit(0);
}

// Ejecutar
main();
