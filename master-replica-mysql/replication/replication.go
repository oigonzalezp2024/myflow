package replication

func Replication() {
	// Pásale los parámetros de conexión de tu contenedor maestro
	masterHost := "127.0.0.1" 
	var masterPort uint16 = 3306 
	user := "root" 
	password := "rootpassword" 
	var serverID uint32 = 100 
	binlogFile := "mysql-bin.000001" // Nombre exacto del archivo binlog
	var binlogPos uint32 = 4         // Posición del binlog

	startReplication(masterHost, masterPort, user, password, serverID, binlogFile, binlogPos)
}
