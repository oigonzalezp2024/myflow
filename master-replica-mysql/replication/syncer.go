package replication

import (
	"context"
	"fmt"
	"log"
	"time"

	"github.com/go-mysql-org/go-mysql/mysql"
	"github.com/go-mysql-org/go-mysql/replication"
)

// StartReplication configura y arranca el syncer para escuchar eventos del binlog de MySQL.
func startReplication(masterHost string, masterPort uint16, user, password string, serverID uint32, binlogFile string, binlogPos uint32) {
	// 1. Configurar el BinlogSyncer
	cfg := replication.BinlogSyncerConfig{
		ServerID: serverID,
		Flavor:   "mysql",
		Host:     masterHost,
		Port:     masterPort,
		User:     user,
		Password: password,
	}

	syncer := replication.NewBinlogSyncer(cfg)
	defer syncer.Close()

	// 2. Iniciar la sincronización desde la posición dada
	streamer, err := syncer.StartSync(mysql.Position{Name: binlogFile, Pos: binlogPos})
	if err != nil {
		log.Fatalf("Error al iniciar la sincronización: %v", err)
	}

	fmt.Printf("Replicación iniciada desde %s:%d\n", binlogFile, binlogPos)

	// 3. Procesar los eventos del Binlog
	for {
		ev, err := streamer.GetEvent(context.Background())
		if err != nil {
			log.Printf("Error al obtener el evento: %v", err)
			time.Sleep(1 * time.Second)
			continue
		}

		// Aquí procesas el evento. En este ejemplo, simplemente lo volcamos a la salida estándar.
		fmt.Println("Evento recibido:")
		ev.Dump(log.Writer())
	}
}
