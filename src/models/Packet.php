<?php


class Packet
{
    private PDO $conn;
    //$conn = new PDO("localhost", "test_db_isa", "root", "");

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function getPacketByRFID(string $RFID): array | string
    {
        $sql = "SELECT * FROM p2_packet
                WHERE tag_id = :rfid";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(':rfid', $RFID);
        $stmt->execute();
        $result = $stmt->fetch();
        $stmt->closeCursor();

        if (!$result) {
            http_response_code(404);

            return "{}";
        }

        return array(
            'id' => $result['id'],
            'quantity' => $result['qte_a_monter'],
            'packet_number' => $result['pack_num'],
            'current_time' => date("H:i:s"),
        );
    }

    public function getPacketmodels(): array | string
    {
        $sql = "select * from init__model";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $stmt->closeCursor();

        if (!$results) {
            http_response_code(400);

            return "{}";
        }

        return $results;
    }

    public function getPacketprod_line(): array | string
    {
        $sql = "select prod_line from p2_packet";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $stmt->closeCursor();

        if (!$results) {
            http_response_code(400);

            return "{}";
        }

        return $results;
    }

    public function getmodelByprod_line(string $prod_lineName): array | string
    {
        $sql = "select model from p2_packet WHERE prod_line LIKE '$prod_lineName%'";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $stmt->closeCursor();

        if (!$results) {
            http_response_code(400);

            return "{}";
        }

        return $results;
    }


    public function getPacketByof_num(string $modelName): array | string
    {
        $sql = "select of_num from p2_packet WHERE model ='$modelName'";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $stmt->closeCursor();

        if (!$results) {
            http_response_code(400);

            return "{}";
        }

        return $results;
    }
    public function getPacketBypack_num(): array | string
    {
        $sql = "SELECT * FROM prod__packet ORDER BY id DESC LIMIT 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        $stmt->closeCursor();

        if (!$result) {
            http_response_code(400);
            return "{}";
        } else {
            return $result;
        }
    }

    public function getPacketByState(string $of_num): array | string
    {
        $sql = "select * from p2_packet WHERE of_num = '$of_num' AND tag_id = '';";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        $stmt->closeCursor();
        // print_r($result);
        $_id = $result["id"];

        $sql2 = "UPDATE p2_packet SET state = '' WHERE of_num = '$of_num';";
        $stmt2 = $this->conn->prepare($sql2);
        $stmt2->execute();
        $stmt2->closeCursor();

        $sql3 = "UPDATE p2_packet SET state = 'here' WHERE id = '$_id';";
        $stmt3 = $this->conn->prepare($sql3);
        $stmt3->execute();
        $stmt3->closeCursor();

        // if (!$result) {
        //     http_response_code(400);

        //     return "{}";
        // }

        return $result;
    }
     public function getTagTriage(): array | string
    {
        $sql = "SELECT tag_rfid from prod__affectation WHERE id ='1'";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetch();
        $stmt->closeCursor();

        if (!$results) {

            return "{}";
        }

        return $results;
    }

    public function insertcontrolPacket(array $data): int
    {
        $all_qte=$data["all_qte"];
        $codedefaut=$data["codedefaut"];
        $tag=$data["tag"];
        $pack_num=$data["pack_num"];
        $id=$data["id"];
        $cur_day=$data["cur_day"];
        $cur_time=$data["cur_time"];
        $prod_line=$data["prod_line"];
        $qte=$data["qte"];
        $qte_fp=$data["qte_fp"];
        $pqte = explode(",", $all_qte);
        $pcodedefaut = explode(",", $codedefaut);
        $length = count($pcodedefaut);
        $lengthqte = count($pqte);

        for ($i = 0; $i < $length; $i++) {
            $sql = "UPDATE p12_control SET $pcodedefaut[$i] = $pqte[$i], cur_day = '$cur_day', cur_time = '$cur_time', prod_line = '$prod_line', qte = '$qte', qte_fp = '$qte_fp', state='no' WHERE pack_num = '$pack_num' AND id = '$id';";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
        }

        //$sql2 = "INSERT INTO p12_control (state) VALUES ('here');";

        //$stmt2 = $this->conn->prepare($sql2);
        //$stmt2->execute();
        //$stmt2->closeCursor();

        return $stmt->rowCount();

    }
    public function insertOF(array $data): int
    {
        $of_num=$data["of_num"];
        $n_of_num = explode(",", $of_num);
        $length = count($n_of_num);

        for ($i = 0; $i < $length; $i++) {

            $sql0 = "SELECT * From db_isa.p1_of WHERE of_num = '$n_of_num[$i]';";
            $stmt0 = $this->conn->prepare($sql0);
            $stmt0->execute();
            $result0 = $stmt0->fetch();
            $stmt0->closeCursor();

            if (!$result0) {

                $sql = "INSERT INTO `db_isa`.`p1_of`(
                    `of_num`,
                    `client`,
                    `assembly_shop`,
                    `start_date`
                )
                SELECT
                    `of_num`,
                    `client`,
                    `assembly_shop`,
                    `start_date`
                FROM
                    `test_db_isa`.`p1_of`
                WHERE
                    `of_num` = '$n_of_num[$i]';";
                $stmt = $this->conn->prepare($sql);
                $stmt->execute();
                $stmt->closeCursor();
                
                //////

                $sql2 = "INSERT INTO `db_isa`.`p2_packet`(
                    `model`,
                    `of_num`,
                    `pack_num`,
                    `prod_line`,
                    `color`,
                    `size`,
                    `qte`
                )
                SELECT
                    `model`,
                    `of_num`,
                    `pack_num`,
                    `prod_line`,
                    `color`,
                    `size`,
                    `qte`
                FROM
                    `test_db_isa`.`p2_packet`
                WHERE
                    `of_num` = '$n_of_num[$i]';";
                $stmt2 = $this->conn->prepare($sql2);
                $stmt2->execute();
                $stmt2->closeCursor();

                $sql3 = "INSERT INTO `db_isa`.`p3_gamme`(
                    `model`,
                    `of_num`,
                    `pack_num`,
                    `operation_code`,
                    `designation`,
                    `unit_time`,
                    `qte_h`
                )
                SELECT
                    `model`,
                    `of_num`,
                    `pack_num`,
                    `operation_code`,
                    `designation`,
                    `unit_time`,
                    `qte_h`
                FROM
                    `test_db_isa`.`p3_gamme`
                WHERE
                    `of_num` = '$n_of_num[$i]';";
                $stmt3 = $this->conn->prepare($sql3);
                $stmt3->execute();
                $stmt3->closeCursor();
                
            }
        }
        return $stmt3->rowCount();

    }
    public function updateOnePacket(array $data): int
    {
        $tag_id = $data["tag_id"];
        $pack_num = $data["pack_num"];

        $sql1 = "UPDATE p2_packet SET tag_id='' WHERE tag_id='$tag_id';";

        $stmt1 = $this->conn->prepare($sql1);
        $stmt1->execute();
        $stmt1->closeCursor();

        $sql = "UPDATE p2_packet
                SET tag_id = '$tag_id'
                WHERE pack_num = '$pack_num';";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();

        return $stmt->rowCount();

    }
    public function updatePacketByState(array $data): int
    {
        $tag_id = $data["tag_id"];
        $of_num = $data["of_num"];
        $pack_num = $data["pack_num"];

        $sql = "UPDATE p2_packet
                SET tag_id = '$tag_id'
                WHERE of_num = '$of_num' AND pack_num = '$pack_num'";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();
        
        //return $this->getPacket($of_num);
        return $stmt->rowCount();
    }
    public function ofs(string $of_num): array | string
    {
        $sql = "SELECT t2.model,`client`,`quantity` FROM `prod__of` t1 INNER JOIN init__model t2 on t1.model_id = t2.id WHERE t1.of_num='$of_num'";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        $stmt->closeCursor();
    
        return $result;
    }

    public function getPackId(string $pack_num, string $prod_line): array | string
    {

        $sql = "SELECT * FROM p2_packet WHERE pack_num = '$pack_num' AND prod_line= '$prod_line';";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        $stmt->closeCursor();

        return $result;

    }

    public function getPack(string $tag_id): array | string
    {
        
        $sql = "SELECT * FROM prod__packet WHERE tag_rfid = '$tag_id';";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        $stmt->closeCursor();

        if($result){

            $pack_num= $result["pack_num"];

            $sql1 = "SELECT t1.`of_num`, t1.`pack_num`, t1.`size`, t1.`quantity`, t2.operation_num FROM prod__packet t1 INNER JOIN prod__pack_operation t2 on t1.pack_num = t2.pack_num WHERE t2.`pack_num`='$pack_num' ORDER BY t2.`id` DESC LIMIT 1;";
            $stmt1 = $this->conn->prepare($sql1);
            $stmt1->execute();
            $result1 = $stmt1->fetch();
            $stmt1->closeCursor();

            if($result1){
                return $result1;
            } else {
                return $result;
            }

        } else {
            return "{}";
        }

    }

    //////////

    public function getcontrolPacketSoft(): array | string
    {
            $sql = "SELECT * from p12_control WHERE state = 'here';";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            $stmt->closeCursor();

            $tag_id = $result["tag_id"];

            if ($tag_id) {

                $sql2 = "UPDATE p12_control SET prod_line = (SELECT prod_line from p2_packet WHERE tag_id = '$tag_id') WHERE tag_id = '$tag_id';";
                $stmt2 = $this->conn->prepare($sql2);
                $stmt2->execute();
                $stmt2->closeCursor();

                $sql3 = "UPDATE p12_control SET state = 'busy' WHERE tag_id = '$tag_id';";
                $stmt3 = $this->conn->prepare($sql3);
                $stmt3->execute();
                $stmt3->closeCursor();

                $sql00 = "INSERT INTO p12_control (state) VALUES ('here');";
                $stmt00 = $this->conn->prepare($sql00);
                $stmt00->execute();
                $stmt00->closeCursor();
                
                $sql10 = "SELECT * from p12_control WHERE tag_id = '$tag_id'";
                $stmt10 = $this->conn->prepare($sql10);
                $stmt10->execute();
                $result10 = $stmt10->fetch();
                $stmt10->closeCursor();
                
                return $result10;

            } else {
                return "";
            }

    }

    ///////

    public function getcontrolPacket(string $prod_line): array | string
    {

        $sql0 = "SELECT * FROM p12_control WHERE prod_line = '$prod_line' AND state = 'busy'; ";
        $stmt0 = $this->conn->prepare($sql0);
        $stmt0->execute();
        $result0 = $stmt0->fetch();
        $stmt0->closeCursor();

        $tag_id0 = $result0["tag_id"];

        if($tag_id0){

            return $result0;

        }else {

            $sql = "SELECT * from p12_control WHERE state = 'here';";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetch();
            $stmt->closeCursor();

            $tag_id = $result["tag_id"];
            $id = $result["id"];

            if ($tag_id) {

                $sql01 = "UPDATE p12_control SET tag_id='' WHERE tag_id='$tag_id' AND id NOT IN ('$id');";
                $stmt01 = $this->conn->prepare($sql01);
                $stmt01->execute();
                $stmt01->closeCursor();

                $sql2 = "UPDATE p12_control SET prod_line = (SELECT prod_line from p2_packet WHERE tag_id = '$tag_id'), pack_num = (SELECT pack_num from p2_packet WHERE tag_id = '$tag_id') WHERE tag_id = '$tag_id';";
                $stmt2 = $this->conn->prepare($sql2);
                $stmt2->execute();
                $stmt2->closeCursor();

                $sql3 = "UPDATE p12_control SET state = 'busy' WHERE tag_id = '$tag_id';";
                $stmt3 = $this->conn->prepare($sql3);
                $stmt3->execute();
                $stmt3->closeCursor();

                $sql00 = "INSERT INTO p12_control (state) VALUES ('here');";
                $stmt00 = $this->conn->prepare($sql00);
                $stmt00->execute();
                $stmt00->closeCursor();
                
                $sql10 = "SELECT * from p12_control WHERE tag_id = '$tag_id'";
                $stmt10 = $this->conn->prepare($sql10);
                $stmt10->execute();
                $result10 = $stmt10->fetch();
                $stmt10->closeCursor();
                
                return $result10;

            } else {
                return "{}";
            }
        }
        
    }
    public function deleteTag(): int
    {
        $sql = "UPDATE prod__affectation SET tag_rfid = '' WHERE id = 1";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $stmt->closeCursor();
    
        return $stmt->rowCount();
    }
    public function getstateTag(string $tag_id): array | string
    {
        $sql = "SELECT * FROM p12_control WHERE tag_id = '$tag_id' AND state='busy'";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        $stmt->closeCursor();

        if($result){
            
            return $result;

        } else {
            return "{}";
        }

    }

    public function allOF(): array | string
    {
        $sql = "select * from prod__of";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll();
        $stmt->closeCursor();

        if (!$results) {
            http_response_code(400);

            return "{}";
        }

        return $results;
    }

    public function insertPacket(array $data): int
    {
        $tag_rfid = $data["tag_rfid"];
        $of_num = $data["of_num"];
        $quantity = $data["quantity"];
        $matelas = $data["matelas"];
        $pack_num = $data["pack_num"];
        $number = $data["number"];
        $size = $data["size"];
        $emp_total = $data["emp_total"];
        $emp_num = $data["emp_num"];
        $lot = $data["lot"];
        $color = $data["color"];
        $prod_line = $data["prod_line"];

        $sql0 = "UPDATE prod__packet SET tag_rfid= NULL WHERE tag_rfid='$tag_rfid'";

        $stmt0 = $this->conn->prepare($sql0);
        $stmt0->execute();
        $stmt0->closeCursor();

        $sql1 = "INSERT INTO `prod__packet`(`tag_rfid`, `of_num`, `lot`, `pack_num`, `color`, `emp_total`, `emp_num`, `matelas`, `number`, `size`, `quantity`, `prod_line`)VALUES ('$tag_rfid','$of_num','$lot','$pack_num','$color','$emp_total','$emp_num','$matelas','$number','$size','$quantity','$prod_line');";

        $stmt1 = $this->conn->prepare($sql1);
        $stmt1->execute();
        $stmt1->closeCursor();

        $sql = "SELECT * FROM prod__of WHERE of_num='$of_num';";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->fetch();
        $stmt->closeCursor();

        $model=$result["model_id"];

        $sql2 = "INSERT INTO `prod__pack_gamme`(`pack_num`, `gamme_id`) SELECT '$pack_num', id FROM prod__gamme WHERE model_id  = '$model';";
        $stmt2 = $this->conn->prepare($sql2);
        $stmt2->execute();
        $stmt2->closeCursor();


        return $stmt2->rowCount();

    }

    
}
