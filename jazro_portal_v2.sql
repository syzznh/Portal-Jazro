-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3307
-- Generation Time: Oct 26, 2026 at 08:15 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `jazro_portal_v2`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `admin_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `admin_name` varchar(150) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`admin_id`, `user_id`, `admin_name`, `phone_number`) VALUES
(1, 1, 'JAZRO Administrator', '018-5778242');

-- --------------------------------------------------------

--
-- Table structure for table `assessment`
--

CREATE TABLE `assessment` (
  `assessment_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `module_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `total_marks` decimal(8,2) NOT NULL,
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `attendance_status` enum('Present','Absent','Late','Excused') NOT NULL,
  `remark` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `session_id`, `student_id`, `admin_id`, `attendance_status`, `remark`) VALUES
(1225, 33, 1, 1, 'Present', NULL),
(1226, 34, 1, 1, 'Present', NULL),
(1227, 35, 1, 1, 'Present', NULL),
(1228, 36, 1, 1, 'Present', NULL),
(1229, 37, 1, 1, 'Absent', NULL),
(1230, 38, 1, 1, 'Present', NULL),
(1231, 39, 1, 1, 'Present', NULL),
(1232, 40, 1, 1, 'Present', NULL),
(1233, 41, 1, 1, 'Present', NULL),
(1234, 42, 1, 1, 'Present', NULL),
(1235, 43, 1, 1, 'Present', NULL),
(1236, 44, 1, 1, 'Present', NULL),
(1237, 45, 1, 1, 'Present', NULL),
(1238, 46, 1, 1, 'Present', NULL),
(1239, 47, 1, 1, 'Present', NULL),
(1240, 48, 1, 1, 'Present', NULL),
(1241, 49, 1, 1, 'Present', NULL),
(1242, 50, 1, 1, 'Present', NULL),
(1243, 51, 1, 1, 'Absent', NULL),
(1244, 52, 1, 1, 'Present', NULL),
(1245, 53, 1, 1, 'Present', NULL),
(1246, 54, 1, 1, 'Absent', NULL),
(1247, 55, 1, 1, 'Present', NULL),
(1248, 56, 1, 1, 'Present', NULL),
(1249, 57, 1, 1, 'Present', NULL),
(1250, 58, 1, 1, 'Present', NULL),
(1251, 59, 1, 1, 'Present', NULL),
(1252, 60, 1, 1, 'Present', NULL),
(1253, 61, 1, 1, 'Present', NULL),
(1254, 62, 1, 1, 'Present', NULL),
(1255, 63, 1, 1, 'Present', NULL),
(1256, 64, 1, 1, 'Present', NULL),
(1257, 65, 1, 1, 'Present', NULL),
(1258, 30, 2, 1, 'Present', NULL),
(1259, 33, 2, 1, 'Present', NULL),
(1260, 34, 2, 1, 'Present', NULL),
(1261, 35, 2, 1, 'Present', NULL),
(1262, 36, 2, 1, 'Present', NULL),
(1263, 37, 2, 1, 'Present', NULL),
(1264, 38, 2, 1, 'Present', NULL),
(1265, 39, 2, 1, 'Present', NULL),
(1266, 40, 2, 1, 'Present', NULL),
(1267, 41, 2, 1, 'Present', NULL),
(1268, 42, 2, 1, 'Present', NULL),
(1269, 43, 2, 1, 'Present', NULL),
(1270, 44, 2, 1, 'Present', NULL),
(1271, 45, 2, 1, 'Present', NULL),
(1272, 46, 2, 1, 'Present', NULL),
(1273, 47, 2, 1, 'Present', NULL),
(1274, 48, 2, 1, 'Absent', NULL),
(1275, 49, 2, 1, 'Present', NULL),
(1276, 50, 2, 1, 'Present', NULL),
(1277, 51, 2, 1, 'Present', NULL),
(1278, 52, 2, 1, 'Present', NULL),
(1279, 53, 2, 1, 'Present', NULL),
(1280, 54, 2, 1, 'Present', NULL),
(1281, 55, 2, 1, 'Present', NULL),
(1282, 56, 2, 1, 'Present', NULL),
(1283, 57, 2, 1, 'Present', NULL),
(1284, 58, 2, 1, 'Present', NULL),
(1285, 59, 2, 1, 'Present', NULL),
(1286, 60, 2, 1, 'Present', NULL),
(1287, 61, 2, 1, 'Absent', NULL),
(1288, 62, 2, 1, 'Absent', NULL),
(1289, 63, 2, 1, 'Present', NULL),
(1290, 64, 2, 1, 'Present', NULL),
(1291, 65, 2, 1, 'Absent', NULL),
(1292, 30, 3, 1, 'Present', NULL),
(1293, 33, 3, 1, 'Absent', NULL),
(1294, 34, 3, 1, 'Present', NULL),
(1295, 35, 3, 1, 'Absent', NULL),
(1296, 36, 3, 1, 'Present', NULL),
(1297, 37, 3, 1, 'Absent', NULL),
(1298, 38, 3, 1, 'Present', NULL),
(1299, 39, 3, 1, 'Absent', NULL),
(1300, 40, 3, 1, 'Present', NULL),
(1301, 41, 3, 1, 'Present', NULL),
(1302, 42, 3, 1, 'Present', NULL),
(1303, 43, 3, 1, 'Present', NULL),
(1304, 44, 3, 1, 'Present', NULL),
(1305, 45, 3, 1, 'Present', NULL),
(1306, 46, 3, 1, 'Present', NULL),
(1307, 47, 3, 1, 'Present', NULL),
(1308, 48, 3, 1, 'Present', NULL),
(1309, 49, 3, 1, 'Absent', NULL),
(1310, 50, 3, 1, 'Present', NULL),
(1311, 51, 3, 1, 'Present', NULL),
(1312, 52, 3, 1, 'Present', NULL),
(1313, 53, 3, 1, 'Present', NULL),
(1314, 54, 3, 1, 'Present', NULL),
(1315, 55, 3, 1, 'Present', NULL),
(1316, 56, 3, 1, 'Present', NULL),
(1317, 57, 3, 1, 'Present', NULL),
(1318, 58, 3, 1, 'Present', NULL),
(1319, 59, 3, 1, 'Present', NULL),
(1320, 60, 3, 1, 'Present', NULL),
(1321, 61, 3, 1, 'Present', NULL),
(1322, 62, 3, 1, 'Present', NULL),
(1323, 63, 3, 1, 'Present', NULL),
(1324, 64, 3, 1, 'Absent', NULL),
(1325, 65, 3, 1, 'Absent', NULL),
(1326, 30, 4, 1, 'Present', NULL),
(1327, 33, 4, 1, 'Present', NULL),
(1328, 34, 4, 1, 'Present', NULL),
(1329, 35, 4, 1, 'Absent', NULL),
(1330, 36, 4, 1, 'Present', NULL),
(1331, 37, 4, 1, 'Present', NULL),
(1332, 38, 4, 1, 'Present', NULL),
(1333, 39, 4, 1, 'Present', NULL),
(1334, 40, 4, 1, 'Present', NULL),
(1335, 41, 4, 1, 'Present', NULL),
(1336, 42, 4, 1, 'Present', NULL),
(1337, 43, 4, 1, 'Absent', NULL),
(1338, 44, 4, 1, 'Present', NULL),
(1339, 45, 4, 1, 'Present', NULL),
(1340, 46, 4, 1, 'Absent', NULL),
(1341, 47, 4, 1, 'Absent', NULL),
(1342, 48, 4, 1, 'Absent', NULL),
(1343, 49, 4, 1, 'Absent', NULL),
(1344, 50, 4, 1, 'Present', NULL),
(1345, 51, 4, 1, 'Absent', NULL),
(1346, 52, 4, 1, 'Absent', NULL),
(1347, 53, 4, 1, 'Absent', NULL),
(1348, 54, 4, 1, 'Present', NULL),
(1349, 55, 4, 1, 'Absent', NULL),
(1350, 56, 4, 1, 'Absent', NULL),
(1351, 57, 4, 1, 'Absent', NULL),
(1352, 58, 4, 1, 'Present', NULL),
(1353, 59, 4, 1, 'Present', NULL),
(1354, 60, 4, 1, 'Absent', NULL),
(1355, 61, 4, 1, 'Present', NULL),
(1356, 62, 4, 1, 'Absent', NULL),
(1357, 63, 4, 1, 'Absent', NULL),
(1358, 64, 4, 1, 'Present', NULL),
(1359, 65, 4, 1, 'Absent', NULL),
(1360, 30, 5, 1, 'Present', NULL),
(1361, 33, 5, 1, 'Present', NULL),
(1362, 34, 5, 1, 'Present', NULL),
(1363, 35, 5, 1, 'Present', NULL),
(1364, 36, 5, 1, 'Present', NULL),
(1365, 37, 5, 1, 'Absent', NULL),
(1366, 38, 5, 1, 'Absent', NULL),
(1367, 39, 5, 1, 'Absent', NULL),
(1368, 40, 5, 1, 'Present', NULL),
(1369, 41, 5, 1, 'Present', NULL),
(1370, 42, 5, 1, 'Present', NULL),
(1371, 43, 5, 1, 'Present', NULL),
(1372, 44, 5, 1, 'Present', NULL),
(1373, 45, 5, 1, 'Present', NULL),
(1374, 46, 5, 1, 'Present', NULL),
(1375, 47, 5, 1, 'Absent', NULL),
(1376, 48, 5, 1, 'Present', NULL),
(1377, 49, 5, 1, 'Present', NULL),
(1378, 50, 5, 1, 'Present', NULL),
(1379, 51, 5, 1, 'Absent', NULL),
(1380, 52, 5, 1, 'Present', NULL),
(1381, 53, 5, 1, 'Present', NULL),
(1382, 54, 5, 1, 'Present', NULL),
(1383, 55, 5, 1, 'Present', NULL),
(1384, 56, 5, 1, 'Present', NULL),
(1385, 57, 5, 1, 'Present', NULL),
(1386, 58, 5, 1, 'Present', NULL),
(1387, 59, 5, 1, 'Present', NULL),
(1388, 60, 5, 1, 'Present', NULL),
(1389, 61, 5, 1, 'Present', NULL),
(1390, 62, 5, 1, 'Present', NULL),
(1391, 63, 5, 1, 'Present', NULL),
(1392, 64, 5, 1, 'Present', NULL),
(1393, 65, 5, 1, 'Present', NULL),
(1394, 30, 6, 1, 'Present', NULL),
(1395, 33, 6, 1, 'Present', NULL),
(1396, 34, 6, 1, 'Present', NULL),
(1397, 35, 6, 1, 'Present', NULL),
(1398, 36, 6, 1, 'Present', NULL),
(1399, 37, 6, 1, 'Present', NULL),
(1400, 38, 6, 1, 'Present', NULL),
(1401, 39, 6, 1, 'Absent', NULL),
(1402, 40, 6, 1, 'Absent', NULL),
(1403, 41, 6, 1, 'Present', NULL),
(1404, 42, 6, 1, 'Present', NULL),
(1405, 43, 6, 1, 'Present', NULL),
(1406, 44, 6, 1, 'Present', NULL),
(1407, 45, 6, 1, 'Present', NULL),
(1408, 46, 6, 1, 'Present', NULL),
(1409, 47, 6, 1, 'Present', NULL),
(1410, 48, 6, 1, 'Present', NULL),
(1411, 49, 6, 1, 'Present', NULL),
(1412, 50, 6, 1, 'Absent', NULL),
(1413, 51, 6, 1, 'Absent', NULL),
(1414, 52, 6, 1, 'Present', NULL),
(1415, 53, 6, 1, 'Absent', NULL),
(1416, 54, 6, 1, 'Present', NULL),
(1417, 55, 6, 1, 'Present', NULL),
(1418, 56, 6, 1, 'Present', NULL),
(1419, 57, 6, 1, 'Present', NULL),
(1420, 58, 6, 1, 'Absent', NULL),
(1421, 59, 6, 1, 'Present', NULL),
(1422, 60, 6, 1, 'Present', NULL),
(1423, 61, 6, 1, 'Present', NULL),
(1424, 62, 6, 1, 'Present', NULL),
(1425, 63, 6, 1, 'Present', NULL),
(1426, 64, 6, 1, 'Present', NULL),
(1427, 65, 6, 1, 'Present', NULL),
(1428, 30, 7, 1, 'Present', NULL),
(1429, 33, 7, 1, 'Present', NULL),
(1430, 34, 7, 1, 'Present', NULL),
(1431, 35, 7, 1, 'Present', NULL),
(1432, 36, 7, 1, 'Present', NULL),
(1433, 37, 7, 1, 'Absent', NULL),
(1434, 38, 7, 1, 'Present', NULL),
(1435, 39, 7, 1, 'Absent', NULL),
(1436, 40, 7, 1, 'Absent', NULL),
(1437, 41, 7, 1, 'Present', NULL),
(1438, 42, 7, 1, 'Absent', NULL),
(1439, 43, 7, 1, 'Present', NULL),
(1440, 44, 7, 1, 'Present', NULL),
(1441, 45, 7, 1, 'Absent', NULL),
(1442, 46, 7, 1, 'Absent', NULL),
(1443, 47, 7, 1, 'Absent', NULL),
(1444, 48, 7, 1, 'Present', NULL),
(1445, 49, 7, 1, 'Absent', NULL),
(1446, 50, 7, 1, 'Absent', NULL),
(1447, 51, 7, 1, 'Absent', NULL),
(1448, 52, 7, 1, 'Present', NULL),
(1449, 53, 7, 1, 'Present', NULL),
(1450, 54, 7, 1, 'Absent', NULL),
(1451, 55, 7, 1, 'Absent', NULL),
(1452, 56, 7, 1, 'Present', NULL),
(1453, 57, 7, 1, 'Absent', NULL),
(1454, 58, 7, 1, 'Present', NULL),
(1455, 59, 7, 1, 'Present', NULL),
(1456, 60, 7, 1, 'Present', NULL),
(1457, 61, 7, 1, 'Absent', NULL),
(1458, 62, 7, 1, 'Absent', NULL),
(1459, 63, 7, 1, 'Absent', NULL),
(1460, 64, 7, 1, 'Absent', NULL),
(1461, 65, 7, 1, 'Present', NULL),
(1462, 30, 8, 1, 'Present', NULL),
(1463, 33, 8, 1, 'Present', NULL),
(1464, 34, 8, 1, 'Present', NULL),
(1465, 35, 8, 1, 'Present', NULL),
(1466, 36, 8, 1, 'Present', NULL),
(1467, 37, 8, 1, 'Present', NULL),
(1468, 38, 8, 1, 'Present', NULL),
(1469, 39, 8, 1, 'Present', NULL),
(1470, 40, 8, 1, 'Present', NULL),
(1471, 41, 8, 1, 'Present', NULL),
(1472, 42, 8, 1, 'Present', NULL),
(1473, 43, 8, 1, 'Present', NULL),
(1474, 44, 8, 1, 'Present', NULL),
(1475, 45, 8, 1, 'Present', NULL),
(1476, 46, 8, 1, 'Present', NULL),
(1477, 47, 8, 1, 'Present', NULL),
(1478, 48, 8, 1, 'Present', NULL),
(1479, 49, 8, 1, 'Present', NULL),
(1480, 50, 8, 1, 'Present', NULL),
(1481, 51, 8, 1, 'Absent', NULL),
(1482, 52, 8, 1, 'Present', NULL),
(1483, 53, 8, 1, 'Present', NULL),
(1484, 54, 8, 1, 'Present', NULL),
(1485, 55, 8, 1, 'Present', NULL),
(1486, 56, 8, 1, 'Present', NULL),
(1487, 57, 8, 1, 'Present', NULL),
(1488, 58, 8, 1, 'Present', NULL),
(1489, 59, 8, 1, 'Present', NULL),
(1490, 60, 8, 1, 'Present', NULL),
(1491, 61, 8, 1, 'Present', NULL),
(1492, 62, 8, 1, 'Present', NULL),
(1493, 63, 8, 1, 'Present', NULL),
(1494, 64, 8, 1, 'Present', NULL),
(1495, 65, 8, 1, 'Present', NULL),
(1496, 30, 9, 1, 'Present', NULL),
(1497, 33, 9, 1, 'Present', NULL),
(1498, 34, 9, 1, 'Present', NULL),
(1499, 35, 9, 1, 'Absent', NULL),
(1500, 36, 9, 1, 'Present', NULL),
(1501, 37, 9, 1, 'Present', NULL),
(1502, 38, 9, 1, 'Absent', NULL),
(1503, 39, 9, 1, 'Present', NULL),
(1504, 40, 9, 1, 'Present', NULL),
(1505, 41, 9, 1, 'Absent', NULL),
(1506, 42, 9, 1, 'Present', NULL),
(1507, 43, 9, 1, 'Present', NULL),
(1508, 44, 9, 1, 'Present', NULL),
(1509, 45, 9, 1, 'Present', NULL),
(1510, 46, 9, 1, 'Present', NULL),
(1511, 47, 9, 1, 'Present', NULL),
(1512, 48, 9, 1, 'Present', NULL),
(1513, 49, 9, 1, 'Present', NULL),
(1514, 50, 9, 1, 'Present', NULL),
(1515, 51, 9, 1, 'Absent', NULL),
(1516, 52, 9, 1, 'Present', NULL),
(1517, 53, 9, 1, 'Present', NULL),
(1518, 54, 9, 1, 'Present', NULL),
(1519, 55, 9, 1, 'Present', NULL),
(1520, 56, 9, 1, 'Present', NULL),
(1521, 57, 9, 1, 'Present', NULL),
(1522, 58, 9, 1, 'Present', NULL),
(1523, 59, 9, 1, 'Present', NULL),
(1524, 60, 9, 1, 'Present', NULL),
(1525, 61, 9, 1, 'Present', NULL),
(1526, 62, 9, 1, 'Present', NULL),
(1527, 63, 9, 1, 'Present', NULL),
(1528, 64, 9, 1, 'Present', NULL),
(1529, 65, 9, 1, 'Absent', NULL),
(1530, 30, 10, 1, 'Present', NULL),
(1531, 33, 10, 1, 'Present', NULL),
(1532, 34, 10, 1, 'Present', NULL),
(1533, 35, 10, 1, 'Absent', NULL),
(1534, 36, 10, 1, 'Present', NULL),
(1535, 37, 10, 1, 'Present', NULL),
(1536, 38, 10, 1, 'Absent', NULL),
(1537, 39, 10, 1, 'Present', NULL),
(1538, 40, 10, 1, 'Absent', NULL),
(1539, 41, 10, 1, 'Absent', NULL),
(1540, 42, 10, 1, 'Present', NULL),
(1541, 43, 10, 1, 'Present', NULL),
(1542, 44, 10, 1, 'Present', NULL),
(1543, 45, 10, 1, 'Absent', NULL),
(1544, 46, 10, 1, 'Present', NULL),
(1545, 47, 10, 1, 'Present', NULL),
(1546, 48, 10, 1, 'Present', NULL),
(1547, 49, 10, 1, 'Present', NULL),
(1548, 50, 10, 1, 'Present', NULL),
(1549, 51, 10, 1, 'Absent', NULL),
(1550, 52, 10, 1, 'Present', NULL),
(1551, 53, 10, 1, 'Absent', NULL),
(1552, 54, 10, 1, 'Absent', NULL),
(1553, 55, 10, 1, 'Present', NULL),
(1554, 56, 10, 1, 'Present', NULL),
(1555, 57, 10, 1, 'Present', NULL),
(1556, 58, 10, 1, 'Present', NULL),
(1557, 59, 10, 1, 'Present', NULL),
(1558, 60, 10, 1, 'Present', NULL),
(1559, 61, 10, 1, 'Present', NULL),
(1560, 62, 10, 1, 'Present', NULL),
(1561, 63, 10, 1, 'Present', NULL),
(1562, 64, 10, 1, 'Present', NULL),
(1563, 65, 10, 1, 'Present', NULL),
(1564, 30, 11, 1, 'Present', NULL),
(1565, 33, 11, 1, 'Present', NULL),
(1566, 34, 11, 1, 'Present', NULL),
(1567, 35, 11, 1, 'Present', NULL),
(1568, 36, 11, 1, 'Present', NULL),
(1569, 37, 11, 1, 'Present', NULL),
(1570, 38, 11, 1, 'Present', NULL),
(1571, 39, 11, 1, 'Present', NULL),
(1572, 40, 11, 1, 'Present', NULL),
(1573, 41, 11, 1, 'Present', NULL),
(1574, 42, 11, 1, 'Present', NULL),
(1575, 43, 11, 1, 'Present', NULL),
(1576, 44, 11, 1, 'Present', NULL),
(1577, 45, 11, 1, 'Present', NULL),
(1578, 46, 11, 1, 'Present', NULL),
(1579, 47, 11, 1, 'Present', NULL),
(1580, 48, 11, 1, 'Present', NULL),
(1581, 49, 11, 1, 'Present', NULL),
(1582, 50, 11, 1, 'Present', NULL),
(1583, 51, 11, 1, 'Present', NULL),
(1584, 52, 11, 1, 'Present', NULL),
(1585, 53, 11, 1, 'Present', NULL),
(1586, 54, 11, 1, 'Present', NULL),
(1587, 55, 11, 1, 'Present', NULL),
(1588, 56, 11, 1, 'Present', NULL),
(1589, 57, 11, 1, 'Present', NULL),
(1590, 58, 11, 1, 'Present', NULL),
(1591, 59, 11, 1, 'Present', NULL),
(1592, 60, 11, 1, 'Present', NULL),
(1593, 61, 11, 1, 'Absent', NULL),
(1594, 62, 11, 1, 'Present', NULL),
(1595, 63, 11, 1, 'Present', NULL),
(1596, 64, 11, 1, 'Present', NULL),
(1597, 65, 11, 1, 'Present', NULL),
(1598, 30, 12, 1, 'Present', NULL),
(1599, 33, 12, 1, 'Present', NULL),
(1600, 34, 12, 1, 'Absent', NULL),
(1601, 35, 12, 1, 'Absent', NULL),
(1602, 36, 12, 1, 'Present', NULL),
(1603, 37, 12, 1, 'Present', NULL),
(1604, 38, 12, 1, 'Present', NULL),
(1605, 39, 12, 1, 'Present', NULL),
(1606, 40, 12, 1, 'Present', NULL),
(1607, 41, 12, 1, 'Absent', NULL),
(1608, 42, 12, 1, 'Absent', NULL),
(1609, 43, 12, 1, 'Present', NULL),
(1610, 44, 12, 1, 'Present', NULL),
(1611, 45, 12, 1, 'Present', NULL),
(1612, 46, 12, 1, 'Present', NULL),
(1613, 47, 12, 1, 'Present', NULL),
(1614, 48, 12, 1, 'Present', NULL),
(1615, 49, 12, 1, 'Present', NULL),
(1616, 50, 12, 1, 'Present', NULL),
(1617, 51, 12, 1, 'Absent', NULL),
(1618, 52, 12, 1, 'Present', NULL),
(1619, 53, 12, 1, 'Present', NULL),
(1620, 54, 12, 1, 'Present', NULL),
(1621, 55, 12, 1, 'Absent', NULL),
(1622, 56, 12, 1, 'Present', NULL),
(1623, 57, 12, 1, 'Absent', NULL),
(1624, 58, 12, 1, 'Present', NULL),
(1625, 59, 12, 1, 'Present', NULL),
(1626, 60, 12, 1, 'Absent', NULL),
(1627, 61, 12, 1, 'Present', NULL),
(1628, 62, 12, 1, 'Absent', NULL),
(1629, 63, 12, 1, 'Absent', NULL),
(1630, 64, 12, 1, 'Present', NULL),
(1631, 65, 12, 1, 'Absent', NULL),
(1632, 30, 13, 1, 'Present', NULL),
(1633, 33, 13, 1, 'Present', NULL),
(1634, 34, 13, 1, 'Present', NULL),
(1635, 35, 13, 1, 'Present', NULL),
(1636, 36, 13, 1, 'Present', NULL),
(1637, 37, 13, 1, 'Absent', NULL),
(1638, 38, 13, 1, 'Absent', NULL),
(1639, 39, 13, 1, 'Absent', NULL),
(1640, 40, 13, 1, 'Absent', NULL),
(1641, 41, 13, 1, 'Absent', NULL),
(1642, 42, 13, 1, 'Present', NULL),
(1643, 43, 13, 1, 'Present', NULL),
(1644, 44, 13, 1, 'Present', NULL),
(1645, 45, 13, 1, 'Present', NULL),
(1646, 46, 13, 1, 'Present', NULL),
(1647, 47, 13, 1, 'Absent', NULL),
(1648, 48, 13, 1, 'Present', NULL),
(1649, 49, 13, 1, 'Present', NULL),
(1650, 50, 13, 1, 'Present', NULL),
(1651, 51, 13, 1, 'Absent', NULL),
(1652, 52, 13, 1, 'Absent', NULL),
(1653, 53, 13, 1, 'Present', NULL),
(1654, 54, 13, 1, 'Present', NULL),
(1655, 55, 13, 1, 'Present', NULL),
(1656, 56, 13, 1, 'Present', NULL),
(1657, 57, 13, 1, 'Present', NULL),
(1658, 58, 13, 1, 'Present', NULL),
(1659, 59, 13, 1, 'Present', NULL),
(1660, 60, 13, 1, 'Present', NULL),
(1661, 61, 13, 1, 'Absent', NULL),
(1662, 62, 13, 1, 'Present', NULL),
(1663, 63, 13, 1, 'Present', NULL),
(1664, 64, 13, 1, 'Present', NULL),
(1665, 65, 13, 1, 'Present', NULL),
(1666, 30, 14, 1, 'Present', NULL),
(1667, 33, 14, 1, 'Present', NULL),
(1668, 34, 14, 1, 'Present', NULL),
(1669, 35, 14, 1, 'Present', NULL),
(1670, 36, 14, 1, 'Present', NULL),
(1671, 37, 14, 1, 'Absent', NULL),
(1672, 38, 14, 1, 'Present', NULL),
(1673, 39, 14, 1, 'Present', NULL),
(1674, 40, 14, 1, 'Absent', NULL),
(1675, 41, 14, 1, 'Absent', NULL),
(1676, 42, 14, 1, 'Absent', NULL),
(1677, 43, 14, 1, 'Present', NULL),
(1678, 44, 14, 1, 'Present', NULL),
(1679, 45, 14, 1, 'Present', NULL),
(1680, 46, 14, 1, 'Present', NULL),
(1681, 47, 14, 1, 'Present', NULL),
(1682, 48, 14, 1, 'Present', NULL),
(1683, 49, 14, 1, 'Present', NULL),
(1684, 50, 14, 1, 'Absent', NULL),
(1685, 51, 14, 1, 'Absent', NULL),
(1686, 52, 14, 1, 'Present', NULL),
(1687, 53, 14, 1, 'Present', NULL),
(1688, 54, 14, 1, 'Present', NULL),
(1689, 55, 14, 1, 'Present', NULL),
(1690, 56, 14, 1, 'Present', NULL),
(1691, 57, 14, 1, 'Present', NULL),
(1692, 58, 14, 1, 'Absent', NULL),
(1693, 59, 14, 1, 'Present', NULL),
(1694, 60, 14, 1, 'Present', NULL),
(1695, 61, 14, 1, 'Present', NULL),
(1696, 62, 14, 1, 'Present', NULL),
(1697, 63, 14, 1, 'Present', NULL),
(1698, 64, 14, 1, 'Present', NULL),
(1699, 65, 14, 1, 'Present', NULL),
(1700, 30, 15, 1, 'Present', NULL),
(1701, 33, 15, 1, 'Present', NULL),
(1702, 34, 15, 1, 'Present', NULL),
(1703, 35, 15, 1, 'Absent', NULL),
(1704, 36, 15, 1, 'Present', NULL),
(1705, 37, 15, 1, 'Present', NULL),
(1706, 38, 15, 1, 'Present', NULL),
(1707, 39, 15, 1, 'Absent', NULL),
(1708, 40, 15, 1, 'Absent', NULL),
(1709, 41, 15, 1, 'Absent', NULL),
(1710, 42, 15, 1, 'Present', NULL),
(1711, 43, 15, 1, 'Present', NULL),
(1712, 44, 15, 1, 'Present', NULL),
(1713, 45, 15, 1, 'Absent', NULL),
(1714, 46, 15, 1, 'Present', NULL),
(1715, 47, 15, 1, 'Absent', NULL),
(1716, 48, 15, 1, 'Absent', NULL),
(1717, 49, 15, 1, 'Absent', NULL),
(1718, 50, 15, 1, 'Absent', NULL),
(1719, 51, 15, 1, 'Absent', NULL),
(1720, 52, 15, 1, 'Absent', NULL),
(1721, 53, 15, 1, 'Present', NULL),
(1722, 54, 15, 1, 'Present', NULL),
(1723, 55, 15, 1, 'Absent', NULL),
(1724, 56, 15, 1, 'Present', NULL),
(1725, 57, 15, 1, 'Present', NULL),
(1726, 58, 15, 1, 'Absent', NULL),
(1727, 59, 15, 1, 'Absent', NULL),
(1728, 60, 15, 1, 'Present', NULL),
(1729, 61, 15, 1, 'Present', NULL),
(1730, 62, 15, 1, 'Present', NULL),
(1731, 63, 15, 1, 'Present', NULL),
(1732, 64, 15, 1, 'Absent', NULL),
(1733, 65, 15, 1, 'Present', NULL),
(1734, 30, 16, 1, 'Absent', NULL),
(1735, 33, 16, 1, 'Present', NULL),
(1736, 34, 16, 1, 'Absent', NULL),
(1737, 35, 16, 1, 'Absent', NULL),
(1738, 36, 16, 1, 'Present', NULL),
(1739, 37, 16, 1, 'Absent', NULL),
(1740, 38, 16, 1, 'Absent', NULL),
(1741, 39, 16, 1, 'Absent', NULL),
(1742, 40, 16, 1, 'Absent', NULL),
(1743, 41, 16, 1, 'Absent', NULL),
(1744, 42, 16, 1, 'Absent', NULL),
(1745, 43, 16, 1, 'Absent', NULL),
(1746, 44, 16, 1, 'Present', NULL),
(1747, 45, 16, 1, 'Absent', NULL),
(1748, 46, 16, 1, 'Absent', NULL),
(1749, 47, 16, 1, 'Absent', NULL),
(1750, 48, 16, 1, 'Absent', NULL),
(1751, 49, 16, 1, 'Absent', NULL),
(1752, 50, 16, 1, 'Absent', NULL),
(1753, 51, 16, 1, 'Absent', NULL),
(1754, 52, 16, 1, 'Absent', NULL),
(1755, 53, 16, 1, 'Absent', NULL),
(1756, 54, 16, 1, 'Absent', NULL),
(1757, 55, 16, 1, 'Absent', NULL),
(1758, 56, 16, 1, 'Absent', NULL),
(1759, 57, 16, 1, 'Absent', NULL),
(1760, 58, 16, 1, 'Absent', NULL),
(1761, 59, 16, 1, 'Absent', NULL),
(1762, 60, 16, 1, 'Absent', NULL),
(1763, 61, 16, 1, 'Absent', NULL),
(1764, 62, 16, 1, 'Absent', NULL),
(1765, 63, 16, 1, 'Absent', NULL),
(1766, 64, 16, 1, 'Absent', NULL),
(1767, 65, 16, 1, 'Absent', NULL),
(1768, 30, 17, 1, 'Present', NULL),
(1769, 33, 17, 1, 'Present', NULL),
(1770, 34, 17, 1, 'Present', NULL),
(1771, 35, 17, 1, 'Present', NULL),
(1772, 36, 17, 1, 'Present', NULL),
(1773, 37, 17, 1, 'Present', NULL),
(1774, 38, 17, 1, 'Present', NULL),
(1775, 39, 17, 1, 'Absent', NULL),
(1776, 40, 17, 1, 'Present', NULL),
(1777, 41, 17, 1, 'Present', NULL),
(1778, 42, 17, 1, 'Present', NULL),
(1779, 43, 17, 1, 'Present', NULL),
(1780, 44, 17, 1, 'Present', NULL),
(1781, 45, 17, 1, 'Present', NULL),
(1782, 46, 17, 1, 'Present', NULL),
(1783, 47, 17, 1, 'Present', NULL),
(1784, 48, 17, 1, 'Present', NULL),
(1785, 49, 17, 1, 'Present', NULL),
(1786, 50, 17, 1, 'Present', NULL),
(1787, 51, 17, 1, 'Present', NULL),
(1788, 52, 17, 1, 'Present', NULL),
(1789, 53, 17, 1, 'Present', NULL),
(1790, 54, 17, 1, 'Present', NULL),
(1791, 55, 17, 1, 'Present', NULL),
(1792, 56, 17, 1, 'Present', NULL),
(1793, 57, 17, 1, 'Present', NULL),
(1794, 58, 17, 1, 'Present', NULL),
(1795, 59, 17, 1, 'Present', NULL),
(1796, 60, 17, 1, 'Present', NULL),
(1797, 61, 17, 1, 'Present', NULL),
(1798, 62, 17, 1, 'Present', NULL),
(1799, 63, 17, 1, 'Present', NULL),
(1800, 64, 17, 1, 'Present', NULL),
(1801, 65, 17, 1, 'Present', NULL),
(1802, 30, 18, 1, 'Present', NULL),
(1803, 33, 18, 1, 'Present', NULL),
(1804, 34, 18, 1, 'Present', NULL),
(1805, 35, 18, 1, 'Present', NULL),
(1806, 36, 18, 1, 'Present', NULL),
(1807, 37, 18, 1, 'Present', NULL),
(1808, 38, 18, 1, 'Present', NULL),
(1809, 39, 18, 1, 'Present', NULL),
(1810, 40, 18, 1, 'Present', NULL),
(1811, 41, 18, 1, 'Present', NULL),
(1812, 42, 18, 1, 'Absent', NULL),
(1813, 43, 18, 1, 'Present', NULL),
(1814, 44, 18, 1, 'Present', NULL),
(1815, 45, 18, 1, 'Present', NULL),
(1816, 46, 18, 1, 'Present', NULL),
(1817, 47, 18, 1, 'Present', NULL),
(1818, 48, 18, 1, 'Present', NULL),
(1819, 49, 18, 1, 'Present', NULL),
(1820, 50, 18, 1, 'Present', NULL),
(1821, 51, 18, 1, 'Present', NULL),
(1822, 52, 18, 1, 'Present', NULL),
(1823, 53, 18, 1, 'Present', NULL),
(1824, 54, 18, 1, 'Present', NULL),
(1825, 55, 18, 1, 'Present', NULL),
(1826, 56, 18, 1, 'Present', NULL),
(1827, 57, 18, 1, 'Present', NULL),
(1828, 58, 18, 1, 'Present', NULL),
(1829, 59, 18, 1, 'Present', NULL),
(1830, 60, 18, 1, 'Present', NULL),
(1831, 61, 18, 1, 'Present', NULL),
(1832, 62, 18, 1, 'Present', NULL),
(1833, 63, 18, 1, 'Present', NULL),
(1834, 64, 18, 1, 'Present', NULL),
(1835, 65, 18, 1, 'Present', NULL),
(1836, 116, 1, 1, 'Present', NULL),
(1837, 116, 2, 1, 'Present', NULL),
(1838, 116, 3, 1, 'Present', NULL),
(1839, 116, 4, 1, 'Present', NULL),
(1840, 116, 5, 1, 'Present', NULL),
(1841, 116, 6, 1, 'Present', NULL),
(1842, 116, 7, 1, 'Present', NULL),
(1843, 116, 8, 1, 'Present', NULL),
(1844, 116, 9, 1, 'Present', NULL),
(1845, 116, 10, 1, 'Present', NULL),
(1846, 116, 11, 1, 'Present', NULL),
(1847, 116, 12, 1, 'Present', NULL),
(1848, 116, 13, 1, 'Present', NULL),
(1849, 116, 14, 1, 'Present', NULL),
(1850, 116, 15, 1, 'Present', NULL),
(1851, 116, 16, 1, 'Present', NULL),
(1852, 116, 17, 1, 'Present', NULL),
(1853, 116, 18, 1, 'Present', NULL),
(1854, 117, 1, 1, 'Present', NULL),
(1855, 117, 2, 1, 'Present', NULL),
(1856, 117, 3, 1, 'Present', NULL),
(1857, 117, 4, 1, 'Present', NULL),
(1858, 117, 5, 1, 'Present', NULL),
(1859, 117, 6, 1, 'Present', NULL),
(1860, 117, 7, 1, 'Present', NULL),
(1861, 117, 8, 1, 'Present', NULL),
(1862, 117, 9, 1, 'Present', NULL),
(1863, 117, 10, 1, 'Present', NULL),
(1864, 117, 11, 1, 'Present', NULL),
(1865, 117, 12, 1, 'Present', NULL),
(1866, 117, 13, 1, 'Present', NULL),
(1867, 117, 14, 1, 'Present', NULL),
(1868, 117, 15, 1, 'Present', NULL),
(1869, 117, 16, 1, 'Present', NULL),
(1870, 117, 17, 1, 'Present', NULL),
(1871, 117, 18, 1, 'Present', NULL),
(1872, 76, 19, 1, 'Present', NULL),
(1873, 77, 19, 1, 'Present', NULL),
(1874, 78, 19, 1, 'Present', NULL),
(1875, 79, 19, 1, 'Present', NULL),
(1876, 80, 19, 1, 'Present', NULL),
(1877, 81, 19, 1, 'Present', NULL),
(1878, 82, 19, 1, 'Absent', NULL),
(1879, 83, 19, 1, 'Present', NULL),
(1880, 84, 19, 1, 'Present', NULL),
(1881, 85, 19, 1, 'Present', NULL),
(1882, 86, 19, 1, 'Present', NULL),
(1883, 87, 19, 1, 'Present', NULL),
(1884, 88, 19, 1, 'Present', NULL),
(1885, 89, 19, 1, 'Present', NULL),
(1886, 90, 19, 1, 'Present', NULL),
(1887, 91, 19, 1, 'Present', NULL),
(1888, 92, 19, 1, 'Present', NULL),
(1889, 94, 19, 1, 'Present', NULL),
(1890, 95, 19, 1, 'Present', NULL),
(1891, 96, 19, 1, 'Present', NULL),
(1892, 97, 19, 1, 'Present', NULL),
(1893, 98, 19, 1, 'Present', NULL),
(1894, 99, 19, 1, 'Present', NULL),
(1895, 100, 19, 1, 'Present', NULL),
(1896, 102, 19, 1, 'Present', NULL),
(1897, 103, 19, 1, 'Present', NULL),
(1898, 104, 19, 1, 'Present', NULL),
(1899, 105, 19, 1, 'Present', NULL),
(1900, 106, 19, 1, 'Present', NULL),
(1901, 107, 19, 1, 'Present', NULL),
(1902, 108, 19, 1, 'Present', NULL),
(1903, 76, 20, 1, 'Present', NULL),
(1904, 77, 20, 1, 'Present', NULL),
(1905, 78, 20, 1, 'Present', NULL),
(1906, 79, 20, 1, 'Present', NULL),
(1907, 80, 20, 1, 'Absent', NULL),
(1908, 81, 20, 1, 'Absent', NULL),
(1909, 82, 20, 1, 'Present', NULL),
(1910, 83, 20, 1, 'Present', NULL),
(1911, 84, 20, 1, 'Present', NULL),
(1912, 85, 20, 1, 'Present', NULL),
(1913, 86, 20, 1, 'Present', NULL),
(1914, 87, 20, 1, 'Present', NULL),
(1915, 88, 20, 1, 'Present', NULL),
(1916, 89, 20, 1, 'Present', NULL),
(1917, 90, 20, 1, 'Present', NULL),
(1918, 91, 20, 1, 'Present', NULL),
(1919, 92, 20, 1, 'Present', NULL),
(1920, 94, 20, 1, 'Present', NULL),
(1921, 95, 20, 1, 'Present', NULL),
(1922, 96, 20, 1, 'Present', NULL),
(1923, 97, 20, 1, 'Present', NULL),
(1924, 98, 20, 1, 'Absent', NULL),
(1925, 99, 20, 1, 'Present', NULL),
(1926, 100, 20, 1, 'Present', NULL),
(1927, 102, 20, 1, 'Present', NULL),
(1928, 103, 20, 1, 'Present', NULL),
(1929, 104, 20, 1, 'Present', NULL),
(1930, 105, 20, 1, 'Present', NULL),
(1931, 106, 20, 1, 'Present', NULL),
(1932, 107, 20, 1, 'Present', NULL),
(1933, 108, 20, 1, 'Absent', NULL),
(1934, 76, 21, 1, 'Present', NULL),
(1935, 77, 21, 1, 'Present', NULL),
(1936, 78, 21, 1, 'Present', NULL),
(1937, 79, 21, 1, 'Present', NULL),
(1938, 80, 21, 1, 'Present', NULL),
(1939, 81, 21, 1, 'Present', NULL),
(1940, 82, 21, 1, 'Present', NULL),
(1941, 83, 21, 1, 'Present', NULL),
(1942, 84, 21, 1, 'Present', NULL),
(1943, 85, 21, 1, 'Present', NULL),
(1944, 86, 21, 1, 'Present', NULL),
(1945, 87, 21, 1, 'Absent', NULL),
(1946, 88, 21, 1, 'Absent', NULL),
(1947, 89, 21, 1, 'Present', NULL),
(1948, 90, 21, 1, 'Present', NULL),
(1949, 91, 21, 1, 'Absent', NULL),
(1950, 92, 21, 1, 'Present', NULL),
(1951, 94, 21, 1, 'Present', NULL),
(1952, 95, 21, 1, 'Absent', NULL),
(1953, 96, 21, 1, 'Present', NULL),
(1954, 97, 21, 1, 'Absent', NULL),
(1955, 98, 21, 1, 'Absent', NULL),
(1956, 99, 21, 1, 'Present', NULL),
(1957, 100, 21, 1, 'Present', NULL),
(1958, 102, 21, 1, 'Absent', NULL),
(1959, 103, 21, 1, 'Absent', NULL),
(1960, 104, 21, 1, 'Present', NULL),
(1961, 105, 21, 1, 'Present', NULL),
(1962, 106, 21, 1, 'Present', NULL),
(1963, 107, 21, 1, 'Present', NULL),
(1964, 108, 21, 1, 'Absent', NULL),
(1965, 76, 22, 1, 'Present', NULL),
(1966, 77, 22, 1, 'Present', NULL),
(1967, 78, 22, 1, 'Present', NULL),
(1968, 79, 22, 1, 'Present', NULL),
(1969, 80, 22, 1, 'Present', NULL),
(1970, 81, 22, 1, 'Present', NULL),
(1971, 82, 22, 1, 'Present', NULL),
(1972, 83, 22, 1, 'Absent', NULL),
(1973, 84, 22, 1, 'Present', NULL),
(1974, 85, 22, 1, 'Present', NULL),
(1975, 86, 22, 1, 'Absent', NULL),
(1976, 90, 22, 1, 'Present', NULL),
(1977, 91, 22, 1, 'Absent', NULL),
(1978, 92, 22, 1, 'Absent', NULL),
(1979, 94, 22, 1, 'Absent', NULL),
(1980, 95, 22, 1, 'Absent', NULL),
(1981, 96, 22, 1, 'Absent', NULL),
(1982, 97, 22, 1, 'Absent', NULL),
(1983, 98, 22, 1, 'Absent', NULL),
(1984, 99, 22, 1, 'Present', NULL),
(1985, 100, 22, 1, 'Absent', NULL),
(1986, 102, 22, 1, 'Absent', NULL),
(1987, 103, 22, 1, 'Present', NULL),
(1988, 104, 22, 1, 'Present', NULL),
(1989, 105, 22, 1, 'Present', NULL),
(1990, 106, 22, 1, 'Absent', NULL),
(1991, 107, 22, 1, 'Present', NULL),
(1992, 108, 22, 1, 'Present', NULL),
(1993, 76, 23, 1, 'Present', NULL),
(1994, 77, 23, 1, 'Absent', NULL),
(1995, 78, 23, 1, 'Absent', NULL),
(1996, 79, 23, 1, 'Present', NULL),
(1997, 80, 23, 1, 'Present', NULL),
(1998, 81, 23, 1, 'Absent', NULL),
(1999, 82, 23, 1, 'Absent', NULL),
(2000, 83, 23, 1, 'Present', NULL),
(2001, 84, 23, 1, 'Absent', NULL),
(2002, 85, 23, 1, 'Present', NULL),
(2003, 86, 23, 1, 'Present', NULL),
(2004, 87, 23, 1, 'Absent', NULL),
(2005, 88, 23, 1, 'Absent', NULL),
(2006, 89, 23, 1, 'Absent', NULL),
(2007, 98, 23, 1, 'Absent', NULL),
(2008, 99, 23, 1, 'Absent', NULL),
(2009, 100, 23, 1, 'Absent', NULL),
(2010, 102, 23, 1, 'Absent', NULL),
(2011, 103, 23, 1, 'Absent', NULL),
(2012, 104, 23, 1, 'Absent', NULL),
(2013, 105, 23, 1, 'Absent', NULL),
(2014, 106, 23, 1, 'Absent', NULL),
(2015, 107, 23, 1, 'Absent', NULL),
(2016, 108, 23, 1, 'Absent', NULL),
(2017, 76, 24, 1, 'Present', NULL),
(2018, 77, 24, 1, 'Present', NULL),
(2019, 78, 24, 1, 'Absent', NULL),
(2020, 79, 24, 1, 'Present', NULL),
(2021, 80, 24, 1, 'Present', NULL),
(2022, 81, 24, 1, 'Absent', NULL),
(2023, 82, 24, 1, 'Present', NULL),
(2024, 83, 24, 1, 'Present', NULL),
(2025, 84, 24, 1, 'Absent', NULL),
(2026, 85, 24, 1, 'Present', NULL),
(2027, 86, 24, 1, 'Present', NULL),
(2028, 87, 24, 1, 'Absent', NULL),
(2029, 88, 24, 1, 'Absent', NULL),
(2030, 89, 24, 1, 'Absent', NULL),
(2031, 98, 24, 1, 'Absent', NULL),
(2032, 99, 24, 1, 'Absent', NULL),
(2033, 100, 24, 1, 'Absent', NULL),
(2034, 102, 24, 1, 'Absent', NULL),
(2035, 103, 24, 1, 'Absent', NULL),
(2036, 104, 24, 1, 'Absent', NULL),
(2037, 105, 24, 1, 'Absent', NULL),
(2038, 106, 24, 1, 'Absent', NULL),
(2039, 107, 24, 1, 'Absent', NULL),
(2040, 108, 24, 1, 'Absent', NULL),
(2041, 78, 25, 1, 'Present', NULL),
(2042, 79, 25, 1, 'Present', NULL),
(2043, 80, 25, 1, 'Present', NULL),
(2044, 81, 25, 1, 'Absent', NULL),
(2045, 82, 25, 1, 'Present', NULL),
(2046, 83, 25, 1, 'Present', NULL),
(2047, 84, 25, 1, 'Present', NULL),
(2048, 85, 25, 1, 'Absent', NULL),
(2049, 86, 25, 1, 'Present', NULL),
(2050, 87, 25, 1, 'Present', NULL),
(2051, 88, 25, 1, 'Present', NULL),
(2052, 89, 25, 1, 'Present', NULL),
(2053, 90, 25, 1, 'Present', NULL),
(2054, 91, 25, 1, 'Present', NULL),
(2055, 92, 25, 1, 'Present', NULL),
(2056, 94, 25, 1, 'Present', NULL),
(2057, 95, 25, 1, 'Present', NULL),
(2058, 96, 25, 1, 'Present', NULL),
(2059, 97, 25, 1, 'Present', NULL),
(2060, 98, 25, 1, 'Present', NULL),
(2061, 99, 25, 1, 'Present', NULL),
(2062, 100, 25, 1, 'Present', NULL),
(2063, 102, 25, 1, 'Present', NULL),
(2064, 103, 25, 1, 'Absent', NULL),
(2065, 104, 25, 1, 'Absent', NULL),
(2066, 105, 25, 1, 'Absent', NULL),
(2067, 106, 25, 1, 'Absent', NULL),
(2068, 107, 25, 1, 'Absent', NULL),
(2069, 108, 25, 1, 'Absent', NULL),
(2070, 82, 26, 1, 'Present', NULL),
(2071, 83, 26, 1, 'Present', NULL),
(2072, 84, 26, 1, 'Present', NULL),
(2073, 85, 26, 1, 'Present', NULL),
(2074, 86, 26, 1, 'Present', NULL),
(2075, 87, 26, 1, 'Present', NULL),
(2076, 88, 26, 1, 'Present', NULL),
(2077, 89, 26, 1, 'Present', NULL),
(2078, 90, 26, 1, 'Present', NULL),
(2079, 91, 26, 1, 'Present', NULL),
(2080, 92, 26, 1, 'Present', NULL),
(2081, 94, 26, 1, 'Present', NULL),
(2082, 95, 26, 1, 'Present', NULL),
(2083, 96, 26, 1, 'Present', NULL),
(2084, 97, 26, 1, 'Present', NULL),
(2085, 98, 26, 1, 'Present', NULL),
(2086, 99, 26, 1, 'Present', NULL),
(2087, 100, 26, 1, 'Present', NULL),
(2088, 102, 26, 1, 'Present', NULL),
(2089, 103, 26, 1, 'Present', NULL),
(2090, 104, 26, 1, 'Present', NULL),
(2091, 105, 26, 1, 'Present', NULL),
(2092, 106, 26, 1, 'Present', NULL),
(2093, 107, 26, 1, 'Present', NULL),
(2094, 108, 26, 1, 'Present', NULL),
(2095, 81, 27, 1, 'Present', NULL),
(2096, 82, 27, 1, 'Absent', NULL),
(2097, 83, 27, 1, 'Present', NULL),
(2098, 84, 27, 1, 'Present', NULL),
(2099, 85, 27, 1, 'Present', NULL),
(2100, 86, 27, 1, 'Present', NULL),
(2101, 87, 27, 1, 'Present', NULL),
(2102, 88, 27, 1, 'Present', NULL),
(2103, 89, 27, 1, 'Present', NULL),
(2104, 90, 27, 1, 'Present', NULL),
(2105, 91, 27, 1, 'Present', NULL),
(2106, 92, 27, 1, 'Present', NULL),
(2107, 94, 27, 1, 'Present', NULL),
(2108, 95, 27, 1, 'Present', NULL),
(2109, 96, 27, 1, 'Present', NULL),
(2110, 97, 27, 1, 'Present', NULL),
(2111, 98, 27, 1, 'Absent', NULL),
(2112, 99, 27, 1, 'Present', NULL),
(2113, 100, 27, 1, 'Present', NULL),
(2114, 102, 27, 1, 'Absent', NULL),
(2115, 103, 27, 1, 'Present', NULL),
(2116, 104, 27, 1, 'Present', NULL),
(2117, 105, 27, 1, 'Present', NULL),
(2118, 106, 27, 1, 'Present', NULL),
(2119, 107, 27, 1, 'Present', NULL),
(2120, 108, 27, 1, 'Present', NULL),
(2121, 83, 28, 1, 'Present', NULL),
(2122, 84, 28, 1, 'Present', NULL),
(2123, 85, 28, 1, 'Present', NULL),
(2124, 86, 28, 1, 'Present', NULL),
(2125, 87, 28, 1, 'Present', NULL),
(2126, 88, 28, 1, 'Present', NULL),
(2127, 89, 28, 1, 'Present', NULL),
(2128, 90, 28, 1, 'Present', NULL),
(2129, 91, 28, 1, 'Absent', NULL),
(2130, 92, 28, 1, 'Present', NULL),
(2131, 94, 28, 1, 'Present', NULL),
(2132, 95, 28, 1, 'Present', NULL),
(2133, 96, 28, 1, 'Present', NULL),
(2134, 97, 28, 1, 'Present', NULL),
(2135, 98, 28, 1, 'Present', NULL),
(2136, 99, 28, 1, 'Present', NULL),
(2137, 100, 28, 1, 'Present', NULL),
(2138, 102, 28, 1, 'Present', NULL),
(2139, 103, 28, 1, 'Present', NULL),
(2140, 104, 28, 1, 'Present', NULL),
(2141, 105, 28, 1, 'Present', NULL),
(2142, 106, 28, 1, 'Present', NULL),
(2143, 107, 28, 1, 'Present', NULL),
(2144, 108, 28, 1, 'Present', NULL),
(2145, 82, 29, 1, 'Present', NULL),
(2146, 83, 29, 1, 'Present', NULL),
(2147, 84, 29, 1, 'Present', NULL),
(2148, 85, 29, 1, 'Present', NULL),
(2149, 86, 29, 1, 'Present', NULL),
(2150, 87, 29, 1, 'Present', NULL),
(2151, 88, 29, 1, 'Present', NULL),
(2152, 89, 29, 1, 'Present', NULL),
(2153, 90, 29, 1, 'Present', NULL),
(2154, 91, 29, 1, 'Present', NULL),
(2155, 92, 29, 1, 'Present', NULL),
(2156, 94, 29, 1, 'Present', NULL),
(2157, 95, 29, 1, 'Present', NULL),
(2158, 96, 29, 1, 'Present', NULL),
(2159, 97, 29, 1, 'Present', NULL),
(2160, 98, 29, 1, 'Present', NULL),
(2161, 99, 29, 1, 'Present', NULL),
(2162, 100, 29, 1, 'Present', NULL),
(2163, 102, 29, 1, 'Present', NULL),
(2164, 103, 29, 1, 'Present', NULL),
(2165, 104, 29, 1, 'Present', NULL),
(2166, 105, 29, 1, 'Present', NULL),
(2167, 106, 29, 1, 'Present', NULL),
(2168, 107, 29, 1, 'Present', NULL),
(2169, 108, 29, 1, 'Present', NULL),
(2170, 85, 30, 1, 'Present', NULL),
(2171, 86, 30, 1, 'Present', NULL),
(2172, 87, 30, 1, 'Present', NULL),
(2173, 88, 30, 1, 'Present', NULL),
(2174, 89, 30, 1, 'Present', NULL),
(2175, 90, 30, 1, 'Absent', NULL),
(2176, 91, 30, 1, 'Present', NULL),
(2177, 92, 30, 1, 'Present', NULL),
(2178, 94, 30, 1, 'Present', NULL),
(2179, 95, 30, 1, 'Present', NULL),
(2180, 96, 30, 1, 'Present', NULL),
(2181, 97, 30, 1, 'Present', NULL),
(2182, 98, 30, 1, 'Present', NULL),
(2183, 99, 30, 1, 'Present', NULL),
(2184, 100, 30, 1, 'Present', NULL),
(2185, 102, 30, 1, 'Present', NULL),
(2186, 103, 30, 1, 'Present', NULL),
(2187, 104, 30, 1, 'Present', NULL),
(2188, 105, 30, 1, 'Present', NULL),
(2189, 106, 30, 1, 'Present', NULL),
(2190, 107, 30, 1, 'Present', NULL),
(2191, 108, 30, 1, 'Present', NULL),
(2192, 86, 31, 1, 'Present', NULL),
(2193, 87, 31, 1, 'Absent', NULL),
(2194, 88, 31, 1, 'Present', NULL),
(2195, 89, 31, 1, 'Present', NULL),
(2196, 90, 31, 1, 'Present', NULL),
(2197, 91, 31, 1, 'Present', NULL),
(2198, 92, 31, 1, 'Present', NULL),
(2199, 94, 31, 1, 'Present', NULL),
(2200, 95, 31, 1, 'Present', NULL),
(2201, 96, 31, 1, 'Present', NULL),
(2202, 97, 31, 1, 'Present', NULL),
(2203, 98, 31, 1, 'Present', NULL),
(2204, 99, 31, 1, 'Present', NULL),
(2205, 100, 31, 1, 'Present', NULL),
(2206, 102, 31, 1, 'Absent', NULL),
(2207, 103, 31, 1, 'Present', NULL),
(2208, 104, 31, 1, 'Absent', NULL),
(2209, 105, 31, 1, 'Absent', NULL),
(2210, 106, 31, 1, 'Absent', NULL),
(2211, 107, 31, 1, 'Absent', NULL),
(2212, 108, 31, 1, 'Absent', NULL),
(2213, 90, 32, 1, 'Absent', NULL),
(2214, 91, 32, 1, 'Absent', NULL),
(2215, 92, 32, 1, 'Absent', NULL),
(2216, 94, 32, 1, 'Absent', NULL),
(2217, 95, 32, 1, 'Absent', NULL),
(2218, 96, 32, 1, 'Absent', NULL),
(2219, 97, 32, 1, 'Absent', NULL),
(2220, 98, 32, 1, 'Absent', NULL),
(2221, 99, 32, 1, 'Absent', NULL),
(2222, 100, 32, 1, 'Absent', NULL),
(2223, 102, 32, 1, 'Absent', NULL),
(2224, 103, 32, 1, 'Absent', NULL),
(2225, 104, 32, 1, 'Absent', NULL),
(2226, 105, 32, 1, 'Absent', NULL),
(2227, 106, 32, 1, 'Absent', NULL),
(2228, 107, 32, 1, 'Absent', NULL),
(2229, 108, 32, 1, 'Absent', NULL),
(2230, 76, 33, 1, 'Present', NULL),
(2231, 77, 33, 1, 'Present', NULL),
(2232, 78, 33, 1, 'Present', NULL),
(2233, 79, 33, 1, 'Present', NULL),
(2234, 80, 33, 1, 'Present', NULL),
(2235, 81, 33, 1, 'Present', NULL),
(2236, 82, 33, 1, 'Present', NULL),
(2237, 83, 33, 1, 'Present', NULL),
(2238, 84, 33, 1, 'Present', NULL),
(2239, 85, 33, 1, 'Absent', NULL),
(2240, 86, 33, 1, 'Absent', NULL),
(2241, 87, 33, 1, 'Present', NULL),
(2242, 88, 33, 1, 'Present', NULL),
(2243, 89, 33, 1, 'Present', NULL),
(2244, 98, 33, 1, 'Present', NULL),
(2245, 99, 33, 1, 'Present', NULL),
(2246, 100, 33, 1, 'Present', NULL),
(2247, 102, 33, 1, 'Present', NULL),
(2248, 103, 33, 1, 'Present', NULL),
(2249, 104, 33, 1, 'Present', NULL),
(2250, 105, 33, 1, 'Present', NULL),
(2251, 106, 33, 1, 'Present', NULL),
(2252, 107, 33, 1, 'Present', NULL),
(2253, 108, 33, 1, 'Present', NULL),
(2254, 99, 34, 1, 'Absent', NULL),
(2255, 100, 34, 1, 'Present', NULL),
(2256, 102, 34, 1, 'Absent', NULL),
(2257, 103, 34, 1, 'Present', NULL),
(2258, 104, 34, 1, 'Present', NULL),
(2259, 105, 34, 1, 'Present', NULL),
(2260, 106, 34, 1, 'Present', NULL),
(2261, 107, 34, 1, 'Present', NULL),
(2262, 108, 34, 1, 'Present', NULL),
(2654, 77, 25, 1, 'Present', NULL),
(2655, 101, 19, 1, 'Present', NULL),
(2656, 101, 20, 1, 'Present', NULL),
(2657, 101, 21, 1, 'Present', NULL),
(2658, 101, 22, 1, 'Present', NULL),
(2659, 101, 23, 1, 'Present', NULL),
(2660, 101, 24, 1, 'Present', NULL),
(2661, 101, 25, 1, 'Present', NULL),
(2662, 101, 26, 1, 'Present', NULL),
(2663, 101, 27, 1, 'Present', NULL),
(2664, 101, 28, 1, 'Present', NULL),
(2665, 101, 29, 1, 'Present', NULL),
(2666, 101, 30, 1, 'Present', NULL),
(2667, 101, 31, 1, 'Present', NULL),
(2668, 101, 32, 1, 'Present', NULL),
(2669, 101, 33, 1, 'Present', NULL),
(2670, 101, 34, 1, 'Present', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `blooket`
--

CREATE TABLE `blooket` (
  `blooket_id` int(11) NOT NULL,
  `blooket_name` varchar(200) NOT NULL,
  `category` varchar(100) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `branch`
--

CREATE TABLE `branch` (
  `branch_id` int(11) NOT NULL,
  `branch_code` varchar(30) NOT NULL,
  `branch_location` varchar(255) DEFAULT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `branch_name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `certificate`
--

CREATE TABLE `certificate` (
  `certificate_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `certificate_type` enum('SB','SP','RC') NOT NULL,
  `certificate_name` varchar(200) NOT NULL,
  `certificate_url` varchar(500) DEFAULT NULL,
  `issue_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `classes`
--

CREATE TABLE `classes` (
  `class_id` int(11) NOT NULL,
  `branch_id` int(11) DEFAULT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `class_code` varchar(50) NOT NULL,
  `class_name` varchar(150) NOT NULL,
  `class_type` enum('Physical','Online') NOT NULL,
  `class_category` enum('BAU','B2B') NOT NULL,
  `status` enum('Active','Inactive','Completed') DEFAULT 'Active',
  `start_time` time DEFAULT NULL,
  `end_time` time DEFAULT NULL,
  `day` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `classes`
--

INSERT INTO `classes` (`class_id`, `branch_id`, `organization_id`, `class_code`, `class_name`, `class_type`, `class_category`, `status`, `start_time`, `end_time`, `day`) VALUES
(1, NULL, NULL, 'JTC G14', 'JTC G14 : 2026 BAU VIRTUAL', 'Online', 'BAU', 'Active', '08:00:00', '10:00:00', 'Wednesday'),
(2, NULL, NULL, 'JTC G15', 'JTC G15 : 2026 BAU VIRTUAL', 'Online', 'BAU', 'Active', '08:00:00', '10:00:00', 'Thursday'),
(3, NULL, NULL, 'JTC G16', 'JTC G16 : 2026 BAU VIRTUAL', 'Online', 'BAU', 'Active', '08:00:00', '10:00:00', 'Tuesday'),
(5, NULL, NULL, 'KTG#02', 'KTG#02 : 2026 BAU PHYSICAL', 'Physical', 'BAU', 'Active', '12:00:00', '02:00:00', 'Saturday'),
(6, NULL, NULL, 'KTG#03', 'KTG#03 : 2026 BAU PHYSICAL', 'Physical', 'BAU', 'Active', '03:00:00', '05:00:00', 'Saturday'),
(7, NULL, NULL, 'KTH#01', 'KTH#01 : 2026 BAU PHYSICAL', 'Physical', 'BAU', 'Active', '09:00:00', '11:00:00', 'Saturday'),
(8, NULL, NULL, 'KTH#02', 'KTH#02 : 2026 BAU PHYSICAL', 'Physical', 'BAU', 'Active', '12:00:00', '02:00:00', 'Saturday'),
(9, NULL, NULL, 'CHU#01', 'CHU#01 : 2026 BAU PHYSICAL', 'Physical', 'BAU', 'Active', '09:00:00', '11:00:00', 'Saturday'),
(10, NULL, NULL, 'CHU#02', 'CHU#02 : 2026 BAU PHYSICAL', 'Physical', 'BAU', 'Active', '12:00:00', '02:00:00', 'Saturday'),
(11, NULL, NULL, 'CYB#01', 'CYB#01 : 2026 BAU PHYSICAL', 'Physical', 'BAU', 'Active', '09:00:00', '11:00:00', 'Sunday'),
(12, NULL, NULL, 'CYB#02', 'CYB#02 : 2026 BAU PHYSICAL', 'Physical', 'BAU', 'Active', '12:00:00', '02:00:00', 'Sunday'),
(13, NULL, NULL, 'CYB#03', 'CYB#03 : 2026 BAU PHYSICAL', 'Physical', 'BAU', 'Active', '03:00:00', '05:00:00', 'Sunday'),
(14, NULL, NULL, 'BGI#01', 'BGI#01 : 2026 BAU PHYSICAL', 'Physical', 'BAU', 'Active', '10:00:00', '12:00:00', 'Saturday'),
(15, NULL, NULL, 'BGI#02', 'BGI#02 : 2026 BAU PHYSICAL', 'Physical', 'BAU', 'Active', '01:00:00', '03:00:00', 'Saturday'),
(16, NULL, NULL, 'DGN#01', 'DGN#01 : 2026 BAU PHYSICAL', 'Physical', 'BAU', 'Active', '03:00:00', '05:00:00', 'Friday'),
(17, NULL, NULL, 'MWT#01', 'MWT#01 : 2026 BAU PHYSICAL', 'Physical', 'BAU', 'Active', '03:00:00', '05:00:00', 'Sunday'),
(18, NULL, NULL, 'LITE - PJY#01', 'LITE - PJY#01 : 2026 BAU PHYSICAL', 'Physical', 'BAU', 'Active', '01:00:00', '03:00:00', 'Sunday'),
(19, NULL, NULL, 'MLT#01', 'MLT#01 : 2026 BAU PHYSICAL', 'Physical', 'BAU', 'Active', '09:00:00', '11:00:00', 'Saturday');

-- --------------------------------------------------------

--
-- Table structure for table `class_session`
--

CREATE TABLE `class_session` (
  `session_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `session_date` date NOT NULL,
  `topic` varchar(255) DEFAULT NULL,
  `status` enum('Scheduled','Completed','Cancelled') DEFAULT 'Scheduled',
  `fee` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `class_session`
--

INSERT INTO `class_session` (`session_id`, `class_id`, `session_date`, `topic`, `status`, `fee`) VALUES
(30, 1, '2026-01-07', 'JTC L2 C10', 'Completed', 37.00),
(33, 1, '2026-01-14', 'JTC L2 C11', 'Completed', 37.00),
(34, 1, '2026-01-21', 'JTC L2 C12', 'Completed', 37.00),
(35, 1, '2026-01-28', 'REV JTC L2', 'Completed', 37.00),
(36, 1, '2026-02-11', 'SPR & JTC L3 C01', 'Completed', 37.00),
(37, 1, '2026-02-18', 'JTC L3 C01', 'Completed', 37.00),
(38, 1, '2026-02-25', 'JTC L3 C02', 'Completed', 37.00),
(39, 1, '2026-03-04', 'JTC L3 C03', 'Completed', 37.00),
(40, 1, '2026-03-11', 'JTC L3 C04', 'Completed', 37.00),
(41, 1, '2026-03-18', 'JTC L3 C05', 'Completed', 37.00),
(42, 1, '2026-03-25', 'JTC L3 C06', 'Completed', 37.00),
(43, 1, '2026-04-01', 'JTC L3 C07', 'Completed', 37.00),
(44, 1, '2026-04-08', 'JTC L3 C08', 'Completed', 37.00),
(45, 1, '2026-04-15', 'JTC L3 C09', 'Completed', 37.00),
(46, 1, '2026-04-22', 'JTC L3 C10', 'Completed', 37.00),
(47, 1, '2026-05-06', 'JTC L3 C11', 'Completed', 37.00),
(48, 1, '2026-05-13', 'JTC L3 C12', 'Completed', 37.00),
(49, 1, '2026-05-20', 'REV L3', 'Completed', 37.00),
(50, 1, '2026-06-03', 'SPR L3 & L4 C01', 'Completed', 37.00),
(51, 1, '2026-06-10', 'JTC L4 C02', 'Completed', 37.00),
(52, 1, '2026-06-17', 'JTC L4 C03', 'Completed', 37.00),
(53, 1, '2026-07-01', 'JTC L4 C04', 'Completed', 37.00),
(54, 1, '2026-07-08', 'JTC L4 C05', 'Completed', 37.00),
(55, 1, '2026-07-15', 'JTC L4 C06', 'Completed', 37.00),
(56, 1, '2026-07-22', 'JTC L4 C07', 'Completed', 37.00),
(57, 1, '2026-07-29', 'JTC L4 C08', 'Completed', 37.00),
(58, 1, '2026-08-05', 'JTC L4 C09', 'Completed', 37.00),
(59, 1, '2026-08-12', 'JTC L4 C10', 'Completed', 37.00),
(60, 1, '2026-08-26', 'JTC L4 C11', 'Completed', 37.00),
(61, 1, '2026-09-02', 'JTC L4 C12', 'Completed', 37.00),
(62, 1, '2026-09-09', 'REV & ASSESSMENT', 'Completed', 37.00),
(63, 1, '2026-09-23', 'SPR L4 & L5 C01', 'Completed', 37.00),
(64, 1, '2026-09-30', 'JTC L5 C02', 'Completed', 37.00),
(65, 1, '2026-10-07', 'JTC L5 C03', 'Completed', 37.00),
(66, 1, '2026-10-14', 'JTC L5 C04', 'Completed', 37.00),
(67, 1, '2026-10-21', 'JTC L5 C05', 'Scheduled', 37.00),
(68, 1, '2026-10-28', 'JTC L5 C06', 'Scheduled', 37.00),
(69, 1, '2026-11-04', 'JTC L5 C07', 'Scheduled', 37.00),
(70, 1, '2026-11-11', 'JTC L5 C08', 'Scheduled', 37.00),
(71, 1, '2026-11-18', 'JTC L5 C09', 'Scheduled', 37.00),
(72, 1, '2026-11-25', 'JTC L5 C10', 'Scheduled', 37.00),
(73, 1, '2026-12-02', 'JTC L5 C11', 'Scheduled', 37.00),
(74, 1, '2026-12-09', 'JTC L5 C12', 'Scheduled', 37.00),
(75, 1, '2026-12-16', 'SPR L5', 'Scheduled', 37.00),
(76, 2, '2026-02-05', 'JTC L1 C01', 'Completed', 50.00),
(77, 2, '2026-02-12', 'JTC L1 C02', 'Completed', 50.00),
(78, 2, '2026-02-19', 'JTC L1 C03', 'Completed', 50.00),
(79, 2, '2026-02-26', 'JTC L1 C04', 'Completed', 50.00),
(80, 2, '2026-03-05', 'JTC L1 C05', 'Completed', 50.00),
(81, 2, '2026-03-12', 'JTC L1 C06', 'Completed', 50.00),
(82, 2, '2026-03-26', 'JTC L1 C07', 'Completed', 50.00),
(83, 2, '2026-04-02', 'JTC L1 C08', 'Completed', 50.00),
(84, 2, '2026-04-09', 'JTC L1 C09', 'Completed', 50.00),
(85, 2, '2026-04-16', 'JTC L1 C10', 'Completed', 50.00),
(86, 2, '2026-04-23', 'JTC L1 C11', 'Completed', 50.00),
(87, 2, '2026-05-07', 'JTC L1 C12', 'Completed', 50.00),
(88, 2, '2026-05-14', 'REV L1', 'Completed', 50.00),
(89, 2, '2026-05-21', 'SPR L1 & L2 C01', 'Completed', 50.00),
(90, 2, '2026-06-04', 'JTC L2 C02', 'Completed', 50.00),
(91, 2, '2026-06-11', 'JTC L2 C03', 'Completed', 50.00),
(92, 2, '2026-06-18', 'JTC L2 C04', 'Completed', 50.00),
(93, 2, '2026-06-25', 'JTC L2 C05', 'Completed', 50.00),
(94, 2, '2026-07-02', 'JTC L2 C05', 'Completed', 50.00),
(95, 2, '2026-07-09', 'JTC L2 C06', 'Completed', 50.00),
(96, 2, '2026-07-16', 'JTC L2 C07', 'Completed', 50.00),
(97, 2, '2026-07-23', 'JTC L2 C08', 'Completed', 50.00),
(98, 2, '2026-07-30', 'JTC L2 C09', 'Completed', 50.00),
(99, 2, '2026-08-06', 'JTC L2 C10', 'Completed', 50.00),
(100, 2, '2026-08-13', 'JTC L2 C11', 'Completed', 50.00),
(101, 2, '2026-08-20', 'HOMEWORK', 'Completed', 50.00),
(102, 2, '2026-08-27', 'JTC L2 C12', 'Completed', 50.00),
(103, 2, '2026-09-03', 'REV & ASSESSMENT', 'Completed', 50.00),
(104, 2, '2026-09-10', 'SPR & JTC L3 C01', 'Completed', 50.00),
(105, 2, '2026-09-17', 'JTC L3 C02', 'Completed', 50.00),
(106, 2, '2026-09-24', 'JTC L3 C03', 'Completed', 50.00),
(107, 2, '2026-10-01', 'JTC L3 C04', 'Completed', 50.00),
(108, 2, '2026-10-08', 'JTC L3 C05', 'Completed', 50.00),
(109, 2, '2026-10-15', 'JTC L3 C06', 'Completed', 50.00),
(110, 2, '2026-10-22', 'JTC L3 C07', 'Completed', 50.00),
(111, 2, '2026-10-29', 'JTC L3 C08', 'Scheduled', 50.00),
(112, 2, '2026-11-05', 'JTC L3 C09', 'Scheduled', 50.00),
(113, 2, '2026-11-12', 'JTC L3 C10', 'Scheduled', 50.00),
(114, 2, '2026-11-19', 'JTC L3 C11', 'Scheduled', 50.00),
(115, 2, '2026-11-26', 'JTC L3 C12', 'Scheduled', 50.00),
(116, 1, '2026-06-24', 'HOMEWORK', 'Completed', 37.00),
(117, 1, '2026-08-19', 'HOMEWORK', 'Completed', 37.00),
(118, 3, '2026-11-05', 'JTC L1 C01', 'Completed', 50.00),
(119, 3, '2026-11-12', 'JTC L1 C02', 'Scheduled', 50.00),
(120, 3, '2026-11-19', 'JTC L1 C03', 'Scheduled', 50.00),
(121, 3, '2026-11-26', 'JTC L1 C04', 'Scheduled', 50.00),
(122, 3, '2026-12-03', 'JTC L1 C05', 'Scheduled', 50.00),
(123, 3, '2026-12-10', 'JTC L1 C06', 'Scheduled', 50.00),
(124, 3, '2026-12-17', 'JTC L1 C07', 'Scheduled', 50.00),
(125, 3, '2026-12-24', 'JTC L1 C08', 'Scheduled', 50.00),
(126, 3, '2026-12-31', 'JTC L1 C09', 'Scheduled', 50.00),
(127, 5, '2026-01-03', 'IM1 C01', 'Completed', 50.00),
(128, 5, '2026-01-03', 'IM1 C04', 'Completed', 50.00),
(129, 5, '2026-01-03', 'IM2 C11', 'Completed', 50.00),
(130, 5, '2026-01-03', 'MBOT 1 IM1 C01', 'Completed', 50.00),
(131, 5, '2026-01-03', 'MBOT 1 JR C03', 'Completed', 50.00),
(132, 5, '2026-01-03', 'MBOT 1 JR C04', 'Completed', 50.00),
(133, 5, '2026-01-03', 'MBOT 1 JR C08', 'Completed', 50.00),
(134, 5, '2026-01-03', 'MBOT 1 JR C12', 'Completed', 50.00),
(135, 5, '2026-01-03', 'REV JR & IM1 C01', 'Completed', 50.00),
(136, 5, '2026-01-10', 'IM1 C05', 'Completed', 50.00),
(137, 5, '2026-01-10', 'IM2 C12', 'Completed', 50.00),
(138, 5, '2026-01-10', 'MBOT 1 IM1 C02', 'Completed', 50.00),
(139, 5, '2026-01-10', 'MBOT 1 JR C04', 'Completed', 50.00),
(140, 5, '2026-01-10', 'MBOT 1 JR C05', 'Completed', 50.00),
(141, 5, '2026-01-10', 'MBOT 1 JR C09', 'Completed', 50.00),
(142, 5, '2026-01-10', 'REV JR', 'Completed', 50.00),
(143, 5, '2026-01-24', 'IM1 C01', 'Completed', 50.00),
(144, 5, '2026-01-24', 'IM1 C06', 'Completed', 50.00),
(145, 5, '2026-01-24', 'MBOT 1 IM1 C03', 'Completed', 50.00),
(146, 5, '2026-01-24', 'MBOT 1 JR C01', 'Completed', 50.00),
(147, 5, '2026-01-24', 'MBOT 1 JR C05', 'Completed', 50.00),
(148, 5, '2026-01-24', 'MBOT 1 JR C06', 'Completed', 50.00),
(149, 5, '2026-01-24', 'MBOT 1 JR C10', 'Completed', 50.00),
(150, 5, '2026-01-24', 'REV IM2', 'Completed', 50.00),
(151, 5, '2026-01-31', 'IM1 C02', 'Completed', 50.00),
(152, 5, '2026-01-31', 'IM1 C07', 'Completed', 50.00),
(153, 5, '2026-01-31', 'MBOT 1 IM1 C04', 'Completed', 50.00),
(154, 5, '2026-01-31', 'MBOT 1 JR C02', 'Completed', 50.00),
(155, 5, '2026-01-31', 'MBOT 1 JR C06', 'Completed', 50.00),
(156, 5, '2026-01-31', 'MBOT 1 JR C07', 'Completed', 50.00),
(157, 5, '2026-01-31', 'MBOT 1 JR C11', 'Completed', 50.00),
(158, 5, '2026-01-31', 'SPR IM2', 'Completed', 50.00),
(159, 5, '2026-02-07', 'ADV C01', 'Completed', 50.00),
(160, 5, '2026-02-07', 'IM1 C03', 'Completed', 50.00),
(161, 5, '2026-02-07', 'IM1 C05', 'Completed', 50.00),
(162, 5, '2026-02-07', 'IM1 CO8', 'Completed', 50.00),
(163, 5, '2026-02-07', 'MBOT 1 JR C01', 'Completed', 50.00),
(164, 5, '2026-02-07', 'MBOT 1 JR C03', 'Completed', 50.00),
(165, 5, '2026-02-07', 'MBOT 1 JR C07', 'Completed', 50.00),
(166, 5, '2026-02-07', 'MBOT 1 JR C08', 'Completed', 50.00),
(167, 5, '2026-02-07', 'MBOT JR C08', 'Completed', 50.00),
(168, 5, '2026-02-14', 'ADV C02', 'Completed', 50.00),
(169, 5, '2026-02-14', 'IM1 C04', 'Completed', 50.00),
(170, 5, '2026-02-14', 'IM1 C06', 'Completed', 50.00),
(171, 5, '2026-02-14', 'IM1 CO9', 'Completed', 50.00),
(172, 5, '2026-02-14', 'MBOT 1 JR C02', 'Completed', 50.00),
(173, 5, '2026-02-14', 'MBOT 1 JR C04', 'Completed', 50.00),
(174, 5, '2026-02-14', 'MBOT 1 JR C12', 'Completed', 50.00),
(175, 5, '2026-02-14', 'MBOT JR C09', 'Completed', 50.00),
(176, 5, '2026-02-21', 'ADV C03', 'Completed', 50.00),
(177, 5, '2026-02-21', 'IM1 C05', 'Completed', 50.00),
(178, 5, '2026-02-21', 'IM1 C07', 'Completed', 50.00),
(179, 5, '2026-02-21', 'IM1 CO10', 'Completed', 50.00),
(180, 5, '2026-02-21', 'MBOT 1 JR C01', 'Completed', 50.00),
(181, 5, '2026-02-21', 'MBOT 1 JR C03', 'Completed', 50.00),
(182, 5, '2026-02-21', 'MBOT 1 JR C09', 'Completed', 50.00),
(183, 5, '2026-02-21', 'MBOT JR C10', 'Completed', 50.00),
(184, 5, '2026-02-21', 'REV JR', 'Completed', 50.00),
(185, 5, '2026-02-28', 'ADV C04', 'Completed', 50.00),
(186, 5, '2026-02-28', 'IM1 C01', 'Completed', 50.00),
(187, 5, '2026-02-28', 'IM1 C06', 'Completed', 50.00),
(188, 5, '2026-02-28', 'IM1 C08', 'Completed', 50.00),
(189, 5, '2026-02-28', 'IM1 CO11', 'Completed', 50.00),
(190, 5, '2026-02-28', 'MBOT 1 JR C02', 'Completed', 50.00),
(191, 5, '2026-02-28', 'MBOT 1 JR C04', 'Completed', 50.00),
(192, 5, '2026-02-28', 'MBOT 1 JR C10', 'Completed', 50.00),
(193, 5, '2026-02-28', 'MBOT JR C05', 'Completed', 50.00),
(194, 5, '2026-02-28', 'MBOT JR C08', 'Completed', 50.00),
(195, 5, '2026-02-28', 'MBOT JR C11', 'Completed', 50.00),
(196, 5, '2026-03-07', 'ADV C05', 'Completed', 50.00),
(197, 5, '2026-03-07', 'IM1 C02', 'Completed', 50.00),
(198, 5, '2026-03-07', 'IM1 C07', 'Completed', 50.00),
(199, 5, '2026-03-07', 'IM1 C09', 'Completed', 50.00),
(200, 5, '2026-03-07', 'IM1 C12', 'Completed', 50.00),
(201, 5, '2026-03-07', 'MBOT 1 JR C03', 'Completed', 50.00),
(202, 5, '2026-03-07', 'MBOT 1 JR C05', 'Completed', 50.00),
(203, 5, '2026-03-07', 'MBOT 1 JR C11', 'Completed', 50.00),
(204, 5, '2026-03-07', 'MBOT JR C06', 'Completed', 50.00),
(205, 5, '2026-03-07', 'MBOT JR C09', 'Completed', 50.00),
(206, 5, '2026-03-07', 'MBOT JR C12', 'Completed', 50.00),
(207, 5, '2026-03-14', 'ADV C06', 'Completed', 50.00),
(208, 5, '2026-03-14', 'IM1 C03', 'Completed', 50.00),
(209, 5, '2026-03-14', 'IM1 C08', 'Completed', 50.00),
(210, 5, '2026-03-14', 'IM1 C10', 'Completed', 50.00),
(211, 5, '2026-03-14', 'MBOT 1 JR C04', 'Completed', 50.00),
(212, 5, '2026-03-14', 'MBOT 1 JR C06', 'Completed', 50.00),
(213, 5, '2026-03-14', 'MBOT 1 JR C12', 'Completed', 50.00),
(214, 5, '2026-03-14', 'MBOT JR C07', 'Completed', 50.00),
(215, 5, '2026-03-14', 'MBOT JR C10', 'Completed', 50.00),
(216, 5, '2026-03-14', 'REV IM1', 'Completed', 50.00),
(217, 5, '2026-03-14', 'REV JR', 'Completed', 50.00),
(218, 5, '2026-03-28', 'ADV C07', 'Completed', 50.00),
(219, 5, '2026-03-28', 'IM1 C01', 'Completed', 50.00),
(220, 5, '2026-03-28', 'IM1 C04', 'Completed', 50.00),
(221, 5, '2026-03-28', 'IM1 C09', 'Completed', 50.00),
(222, 5, '2026-03-28', 'IM1 C11', 'Completed', 50.00),
(223, 5, '2026-03-28', 'MBOT 1 JR C05', 'Completed', 50.00),
(224, 5, '2026-03-28', 'MBOT 1 JR C07', 'Completed', 50.00),
(225, 5, '2026-03-28', 'MBOT JR C08', 'Completed', 50.00),
(226, 5, '2026-03-28', 'MBOT JR C11', 'Completed', 50.00),
(227, 5, '2026-03-28', 'REV JR', 'Completed', 50.00),
(228, 5, '2026-03-28', 'SPR IM1', 'Completed', 50.00),
(229, 5, '2026-04-04', 'ADV C08', 'Completed', 50.00),
(230, 5, '2026-04-04', 'IM1 C01', 'Completed', 50.00),
(231, 5, '2026-04-04', 'IM1 C02', 'Completed', 50.00),
(232, 5, '2026-04-04', 'IM1 C05', 'Completed', 50.00),
(233, 5, '2026-04-04', 'IM1 C10', 'Completed', 50.00),
(234, 5, '2026-04-04', 'IM1 C12', 'Completed', 50.00),
(235, 5, '2026-04-04', 'IM2 C01', 'Completed', 50.00),
(236, 5, '2026-04-04', 'MBOT 1 JR C06', 'Completed', 50.00),
(237, 5, '2026-04-04', 'MBOT 1 JR C08', 'Completed', 50.00),
(238, 5, '2026-04-04', 'MBOT JR C09', 'Completed', 50.00),
(239, 5, '2026-04-04', 'MBOT JR C12', 'Completed', 50.00),
(240, 5, '2026-04-11', 'IM1 C02', 'Completed', 50.00),
(241, 5, '2026-04-11', 'IM1 C03', 'Completed', 50.00),
(242, 5, '2026-04-11', 'IM1 C06', 'Completed', 50.00),
(243, 5, '2026-04-11', 'IM1 C11', 'Completed', 50.00),
(244, 5, '2026-04-11', 'IM2 C02', 'Completed', 50.00),
(245, 5, '2026-04-11', 'MBOT 1 JR C07', 'Completed', 50.00),
(246, 5, '2026-04-11', 'MBOT 1 JR C09', 'Completed', 50.00),
(247, 5, '2026-04-11', 'MBOT JR C10', 'Completed', 50.00),
(248, 5, '2026-04-11', 'REV & ASSESSMENT', 'Completed', 50.00),
(249, 5, '2026-04-11', 'REV IM1', 'Completed', 50.00),
(250, 5, '2026-04-18', 'IM1 C01', 'Completed', 50.00),
(251, 5, '2026-04-18', 'IM1 C03', 'Completed', 50.00),
(252, 5, '2026-04-18', 'IM1 C04', 'Completed', 50.00),
(253, 5, '2026-04-18', 'IM1 C07', 'Completed', 50.00),
(254, 5, '2026-04-18', 'IM1 C12', 'Completed', 50.00),
(255, 5, '2026-04-18', 'IM2 C03', 'Completed', 50.00),
(256, 5, '2026-04-18', 'MBOT 1 JR C08', 'Completed', 50.00),
(257, 5, '2026-04-18', 'MBOT 1 JR C10', 'Completed', 50.00),
(258, 5, '2026-04-18', 'MBOT JR C11', 'Completed', 50.00),
(259, 5, '2026-04-18', 'SPR ADV', 'Completed', 50.00),
(260, 5, '2026-04-18', 'SPR IM1', 'Completed', 50.00),
(261, 5, '2026-04-25', 'IM1 C02', 'Completed', 50.00),
(262, 5, '2026-04-25', 'IM1 C04', 'Completed', 50.00),
(263, 5, '2026-04-25', 'IM1 C05', 'Completed', 50.00),
(264, 5, '2026-04-25', 'IM1 C08', 'Completed', 50.00),
(265, 5, '2026-04-25', 'IM2 C01', 'Completed', 50.00),
(266, 5, '2026-04-25', 'IM2 C04', 'Completed', 50.00),
(267, 5, '2026-04-25', 'JAZ C01', 'Completed', 50.00),
(268, 5, '2026-04-25', 'MBOT 1 JR C09', 'Completed', 50.00),
(269, 5, '2026-04-25', 'MBOT 1 JR C11', 'Completed', 50.00),
(270, 5, '2026-04-25', 'MBOT JR C12', 'Completed', 50.00),
(271, 5, '2026-04-25', 'REV IM1', 'Completed', 50.00),
(272, 5, '2026-05-02', 'IM1 C03', 'Completed', 50.00),
(273, 5, '2026-05-02', 'IM1 C05', 'Completed', 50.00),
(274, 5, '2026-05-02', 'IM1 C06', 'Completed', 50.00),
(275, 5, '2026-05-02', 'IM1 C09', 'Completed', 50.00),
(276, 5, '2026-05-02', 'IM2 C02', 'Completed', 50.00),
(277, 5, '2026-05-02', 'IM2 C05', 'Completed', 50.00),
(278, 5, '2026-05-02', 'JAZ C02', 'Completed', 50.00),
(279, 5, '2026-05-02', 'MBOT 1 JR C10', 'Completed', 50.00),
(280, 5, '2026-05-02', 'MBOT JR C12', 'Completed', 50.00),
(281, 5, '2026-05-02', 'REV & ASSESSMENT', 'Completed', 50.00),
(282, 5, '2026-05-02', 'SPR IM1', 'Completed', 50.00),
(283, 5, '2026-05-09', 'IM1 C01', 'Completed', 50.00),
(284, 5, '2026-05-09', 'IM1 C04', 'Completed', 50.00),
(285, 5, '2026-05-09', 'IM1 C06', 'Completed', 50.00),
(286, 5, '2026-05-09', 'IM1 C07', 'Completed', 50.00),
(287, 5, '2026-05-09', 'IM1 C10', 'Completed', 50.00),
(288, 5, '2026-05-09', 'IM2 C01', 'Completed', 50.00),
(289, 5, '2026-05-09', 'IM2 C03', 'Completed', 50.00),
(290, 5, '2026-05-09', 'IM2 C06', 'Completed', 50.00),
(291, 5, '2026-05-09', 'JAZ C03', 'Completed', 50.00),
(292, 5, '2026-05-09', 'MBOT 1 JR C11', 'Completed', 50.00),
(293, 5, '2026-05-09', 'REV & ASSESSMENT', 'Completed', 50.00),
(294, 5, '2026-05-16', 'IM1 C01', 'Completed', 50.00),
(295, 5, '2026-05-16', 'IM1 C02', 'Completed', 50.00),
(296, 5, '2026-05-16', 'IM1 C05', 'Completed', 50.00),
(297, 5, '2026-05-16', 'IM1 C07', 'Completed', 50.00),
(298, 5, '2026-05-16', 'IM1 C08', 'Completed', 50.00),
(299, 5, '2026-05-16', 'IM1 C11', 'Completed', 50.00),
(300, 5, '2026-05-16', 'IM2 C02', 'Completed', 50.00),
(301, 5, '2026-05-16', 'IM2 C04', 'Completed', 50.00),
(302, 5, '2026-05-16', 'IM2 C07', 'Completed', 50.00),
(303, 5, '2026-05-16', 'JAZ C04', 'Completed', 50.00),
(304, 5, '2026-05-16', 'MBOT 1 JR C12', 'Completed', 50.00),
(305, 5, '2026-05-23', 'IM1 C12', 'Completed', 50.00),
(306, 5, '2026-05-23', 'IM2 C05', 'Completed', 50.00),
(307, 5, '2026-05-23', 'JAZ C05', 'Completed', 50.00),
(308, 5, '2026-05-23', 'REV JR', 'Completed', 50.00),
(309, 5, '2026-06-06', 'IM1 C08', 'Completed', 50.00),
(310, 5, '2026-06-06', 'IM1 C09', 'Completed', 50.00),
(311, 5, '2026-06-06', 'IM2 C03', 'Completed', 50.00),
(312, 5, '2026-06-06', 'IM2 C05', 'Completed', 50.00),
(313, 5, '2026-06-06', 'JAZ C06', 'Completed', 50.00),
(314, 5, '2026-06-06', 'MBOT JR C01', 'Completed', 50.00),
(315, 5, '2026-06-13', 'IM1 C02', 'Completed', 50.00),
(316, 5, '2026-06-13', 'IM1 C04', 'Completed', 50.00),
(317, 5, '2026-06-13', 'IM1 C05', 'Completed', 50.00),
(318, 5, '2026-06-13', 'IM1 C08', 'Completed', 50.00),
(319, 5, '2026-06-13', 'IM1 C10', 'Completed', 50.00),
(320, 5, '2026-06-13', 'IM1 C11', 'Completed', 50.00),
(321, 5, '2026-06-13', 'IM2 C05', 'Completed', 50.00),
(322, 5, '2026-06-13', 'IM2 C07', 'Completed', 50.00),
(323, 5, '2026-06-13', 'IM2 C10', 'Completed', 50.00),
(324, 5, '2026-06-13', 'JAZ C07', 'Completed', 50.00),
(325, 5, '2026-06-13', 'MBOT JR C01', 'Completed', 50.00),
(326, 5, '2026-06-13', 'SPR IM1', 'Completed', 50.00),
(327, 5, '2026-06-20', 'IM1 C03', 'Completed', 50.00),
(328, 5, '2026-06-20', 'IM1 C04', 'Completed', 50.00),
(329, 5, '2026-06-20', 'IM1 C05', 'Completed', 50.00),
(330, 5, '2026-06-20', 'IM1 C06', 'Completed', 50.00),
(331, 5, '2026-06-20', 'IM1 C09', 'Completed', 50.00),
(332, 5, '2026-06-20', 'IM1 C11', 'Completed', 50.00),
(333, 5, '2026-06-20', 'IM1 C12', 'Completed', 50.00),
(334, 5, '2026-06-20', 'IM2 C01', 'Completed', 50.00),
(335, 5, '2026-06-20', 'IM2 C06', 'Completed', 50.00),
(336, 5, '2026-06-20', 'IM2 C08', 'Completed', 50.00),
(337, 5, '2026-06-20', 'IM2 C11', 'Completed', 50.00),
(338, 5, '2026-06-20', 'JAZ C08', 'Completed', 50.00),
(339, 5, '2026-06-20', 'MBOT JR C02', 'Completed', 50.00),
(340, 5, '2026-06-27', 'IM1 C05', 'Completed', 50.00),
(341, 5, '2026-07-04', 'IM1 C05', 'Completed', 50.00),
(342, 5, '2026-07-04', 'IM1 C06', 'Completed', 50.00),
(343, 5, '2026-07-04', 'IM1 C07', 'Completed', 50.00),
(344, 5, '2026-07-04', 'IM1 C08', 'Completed', 50.00),
(345, 5, '2026-07-04', 'IM2 C01', 'Completed', 50.00),
(346, 5, '2026-07-04', 'IM2 C03', 'Completed', 50.00),
(347, 5, '2026-07-04', 'IM2 C08', 'Completed', 50.00),
(348, 5, '2026-07-04', 'IM2 C10', 'Completed', 50.00),
(349, 5, '2026-07-04', 'JAZ C10', 'Completed', 50.00),
(350, 5, '2026-07-04', 'MBOT JR C01', 'Completed', 50.00),
(351, 5, '2026-07-04', 'MBOT JR C04', 'Completed', 50.00),
(352, 5, '2026-07-04', 'REV & ASSESSMENT', 'Completed', 50.00),
(353, 5, '2026-07-11', 'ADV C01', 'Completed', 50.00),
(354, 5, '2026-07-11', 'IM1 C08', 'Completed', 50.00),
(355, 5, '2026-07-11', 'IM2 C09', 'Completed', 50.00),
(356, 5, '2026-07-11', 'JAZ C11', 'Completed', 50.00),
(357, 5, '2026-07-11', 'MBOT JR C02', 'Completed', 50.00),
(358, 5, '2026-07-18', 'IM1 C08', 'Completed', 50.00),
(359, 5, '2026-07-18', 'IM1 C09', 'Completed', 50.00),
(360, 5, '2026-07-18', 'IM1 C11', 'Completed', 50.00),
(361, 5, '2026-07-18', 'IM2 C07', 'Completed', 50.00),
(362, 5, '2026-07-18', 'IM2 C10', 'Completed', 50.00),
(363, 5, '2026-07-18', 'IM2 C11', 'Completed', 50.00),
(364, 5, '2026-07-18', 'JAZ C12', 'Completed', 50.00),
(365, 5, '2026-07-18', 'MBOT JR C02', 'Completed', 50.00),
(366, 5, '2026-07-18', 'MBOT JR C03', 'Completed', 50.00),
(367, 5, '2026-07-18', 'MBOT JR C08', 'Completed', 50.00),
(368, 5, '2026-07-18', 'MBOT JR C11', 'Completed', 50.00),
(369, 5, '2026-07-25', 'IM1 C09', 'Completed', 50.00),
(370, 5, '2026-07-25', 'IM1 C11', 'Completed', 50.00),
(371, 5, '2026-07-25', 'IM1 C12', 'Completed', 50.00),
(372, 5, '2026-07-25', 'IM2 C10', 'Completed', 50.00),
(373, 5, '2026-07-25', 'IM2 C11', 'Completed', 50.00),
(374, 5, '2026-07-25', 'IM2 C12', 'Completed', 50.00),
(375, 5, '2026-07-25', 'JAZ C13', 'Completed', 50.00),
(376, 5, '2026-07-25', 'MBOT JR C04', 'Completed', 50.00),
(377, 5, '2026-07-25', 'MBOT JR C07', 'Completed', 50.00),
(378, 5, '2026-07-25', 'MBOT JR C09', 'Completed', 50.00),
(379, 5, '2026-07-25', 'MBOT JR C12', 'Completed', 50.00),
(380, 5, '2026-07-25', 'REV & ASSESSMENT', 'Completed', 50.00),
(381, 5, '2026-08-01', 'ADV C08 & REV', 'Completed', 50.00),
(382, 5, '2026-08-01', 'IM1 C04', 'Completed', 50.00),
(383, 5, '2026-08-01', 'IM1 C06', 'Completed', 50.00),
(384, 5, '2026-08-01', 'IM1 C07', 'Completed', 50.00),
(385, 5, '2026-08-01', 'IM1 C9,10', 'Completed', 50.00),
(386, 5, '2026-08-01', 'IM2 C06', 'Completed', 50.00),
(387, 5, '2026-08-01', 'IM2 C06,C07', 'Completed', 50.00),
(388, 5, '2026-08-01', 'JAZ C14', 'Completed', 50.00),
(389, 5, '2026-08-01', 'MBOT JR C05', 'Completed', 50.00),
(390, 5, '2026-08-01', 'REV IM1', 'Completed', 50.00),
(391, 5, '2026-08-01', 'REV IM2', 'Completed', 50.00),
(392, 5, '2026-08-01', 'REV JR, IM1 C06', 'Completed', 50.00),
(393, 5, '2026-08-08', 'ADV C08 & REV', 'Completed', 50.00),
(394, 5, '2026-08-08', 'ASSESSMENT IM2', 'Completed', 50.00),
(395, 5, '2026-08-08', 'IM1 C05', 'Completed', 50.00),
(396, 5, '2026-08-08', 'IM1 C07', 'Completed', 50.00),
(397, 5, '2026-08-08', 'IM1 C08', 'Completed', 50.00),
(398, 5, '2026-08-08', 'IM2 C01', 'Completed', 50.00),
(399, 5, '2026-08-08', 'IM2 C07', 'Completed', 50.00),
(400, 5, '2026-08-08', 'JAZ C15', 'Completed', 50.00),
(401, 5, '2026-08-08', 'MBOT JR C05', 'Completed', 50.00),
(402, 5, '2026-08-08', 'MBOT JR C06', 'Completed', 50.00),
(403, 5, '2026-08-08', 'REV IM1', 'Completed', 50.00),
(404, 5, '2026-08-08', 'REV IM2', 'Completed', 50.00),
(405, 5, '2026-08-08', 'REV JR', 'Completed', 50.00),
(406, 5, '2026-08-08', 'REV JR, IM1 C06', 'Completed', 50.00),
(407, 5, '2026-08-15', 'ADV C01', 'Completed', 50.00),
(408, 5, '2026-08-15', 'ADV C08 & REV', 'Completed', 50.00),
(409, 5, '2026-08-15', 'IM1 C06', 'Completed', 50.00),
(410, 5, '2026-08-15', 'IM1 C08', 'Completed', 50.00),
(411, 5, '2026-08-15', 'IM1 C09', 'Completed', 50.00),
(412, 5, '2026-08-15', 'IM2 C01', 'Completed', 50.00),
(413, 5, '2026-08-15', 'IM2 C02', 'Completed', 50.00),
(414, 5, '2026-08-15', 'IM2 C08', 'Completed', 50.00),
(415, 5, '2026-08-15', 'JAZ C16', 'Completed', 50.00),
(416, 5, '2026-08-15', 'MBOT JR C07', 'Completed', 50.00),
(417, 5, '2026-08-22', 'JCUP', 'Completed', 50.00),
(418, 5, '2026-08-29', 'ADV C01', 'Completed', 50.00),
(419, 5, '2026-08-29', 'ADV C03', 'Completed', 50.00),
(420, 5, '2026-08-29', 'ADV C08 & REV', 'Completed', 50.00),
(421, 5, '2026-08-29', 'ASSESMENT IM2 & ADV C01', 'Completed', 50.00),
(422, 5, '2026-08-29', 'IM1 C06', 'Completed', 50.00),
(423, 5, '2026-08-29', 'IM1 C08', 'Completed', 50.00),
(424, 5, '2026-08-29', 'IM1 C09', 'Completed', 50.00),
(425, 5, '2026-08-29', 'IM2 C02', 'Completed', 50.00),
(426, 5, '2026-08-29', 'IM2 C03', 'Completed', 50.00),
(427, 5, '2026-08-29', 'IM2 C11', 'Completed', 50.00),
(428, 5, '2026-08-29', 'JAZ C17', 'Completed', 50.00),
(429, 5, '2026-08-29', 'MBOT 1 IM1 C01', 'Completed', 50.00),
(430, 5, '2026-08-29', 'MBOT JR C01', 'Completed', 50.00),
(431, 5, '2026-08-29', 'MBOT JR C04', 'Completed', 50.00),
(432, 5, '2026-08-29', 'MBOT JR C08', 'Completed', 50.00),
(433, 5, '2026-08-29', 'REV IM1', 'Completed', 50.00),
(434, 5, '2026-09-05', 'ADV C02', 'Completed', 50.00),
(435, 5, '2026-09-05', 'IM1 C07', 'Completed', 50.00),
(436, 5, '2026-09-05', 'IM1 C08', 'Completed', 50.00),
(437, 5, '2026-09-05', 'IM1 C10', 'Completed', 50.00),
(438, 5, '2026-09-05', 'IM2 C03', 'Completed', 50.00),
(439, 5, '2026-09-05', 'JAZ C01', 'Completed', 50.00),
(440, 5, '2026-09-05', 'JAZ C17 & JAZ C18', 'Completed', 50.00),
(441, 5, '2026-09-05', 'JAZ C18', 'Completed', 50.00),
(442, 5, '2026-09-05', 'MBOT 1 IM1 C02', 'Completed', 50.00),
(443, 5, '2026-09-05', 'MBOT JR C02', 'Completed', 50.00),
(444, 5, '2026-09-05', 'MBOT JR C05', 'Completed', 50.00),
(445, 5, '2026-09-05', 'MBOT JR C09', 'Completed', 50.00),
(446, 5, '2026-09-05', 'SPR & ADV C01, ADV C02', 'Completed', 50.00),
(447, 5, '2026-09-05', 'SPR & IM2 C03', 'Completed', 50.00),
(448, 5, '2026-09-05', 'SPR IM2 & ADV C02', 'Completed', 50.00),
(449, 5, '2026-09-05', 'SPR IM2 C04', 'Completed', 50.00),
(450, 5, '2026-09-05', 'SPR IM2 C3, IM2 C4', 'Completed', 50.00),
(451, 5, '2026-09-12', 'ADV C02', 'Completed', 50.00),
(452, 5, '2026-09-12', 'ADV C03', 'Completed', 50.00),
(453, 5, '2026-09-12', 'IM1 C06', 'Completed', 50.00),
(454, 5, '2026-09-12', 'IM1 C08', 'Completed', 50.00),
(455, 5, '2026-09-12', 'IM1 C09', 'Completed', 50.00),
(456, 5, '2026-09-12', 'IM1 C11', 'Completed', 50.00),
(457, 5, '2026-09-12', 'IM2 C04', 'Completed', 50.00),
(458, 5, '2026-09-12', 'IM2 C05', 'Completed', 50.00),
(459, 5, '2026-09-12', 'JAZ C02', 'Completed', 50.00),
(460, 5, '2026-09-12', 'JAZ C19', 'Completed', 50.00),
(461, 5, '2026-09-12', 'MBOT 1 IM1 C03', 'Completed', 50.00),
(462, 5, '2026-09-12', 'MBOT JR C02', 'Completed', 50.00),
(463, 5, '2026-09-12', 'MBOT JR C03', 'Completed', 50.00),
(464, 5, '2026-09-12', 'MBOT JR C04', 'Completed', 50.00),
(465, 5, '2026-09-12', 'MBOT JR C10', 'Completed', 50.00),
(466, 5, '2026-09-19', 'ADV C04', 'Completed', 50.00),
(467, 5, '2026-09-19', 'IM1 C07', 'Completed', 50.00),
(468, 5, '2026-09-19', 'IM1 C09', 'Completed', 50.00),
(469, 5, '2026-09-19', 'IM1 C10', 'Completed', 50.00),
(470, 5, '2026-09-19', 'IM2 C05', 'Completed', 50.00),
(471, 5, '2026-09-19', 'IM2 C06', 'Completed', 50.00),
(472, 5, '2026-09-19', 'JAZ C03', 'Completed', 50.00),
(473, 5, '2026-09-19', 'JAZ C20', 'Completed', 50.00),
(474, 5, '2026-09-19', 'MBOT 1 IM1 C04', 'Completed', 50.00),
(475, 5, '2026-09-19', 'MBOT JR C03', 'Completed', 50.00),
(476, 5, '2026-09-19', 'MBOT JR C05', 'Completed', 50.00),
(477, 5, '2026-09-19', 'MBOT JR C11', 'Completed', 50.00),
(478, 5, '2026-09-19', 'SPR & ADV C04', 'Completed', 50.00),
(479, 5, '2026-09-19', 'SPR & IM2 C05', 'Completed', 50.00),
(480, 5, '2026-09-26', 'ADV C05', 'Completed', 50.00),
(481, 5, '2026-09-26', 'IM1 C08', 'Completed', 50.00),
(482, 5, '2026-09-26', 'IM1 C10', 'Completed', 50.00),
(483, 5, '2026-09-26', 'IM1 C11', 'Completed', 50.00),
(484, 5, '2026-09-26', 'IM2 C06', 'Completed', 50.00),
(485, 5, '2026-09-26', 'IM2 C06 & IM2 C07', 'Completed', 50.00),
(486, 5, '2026-09-26', 'IM2 C07', 'Completed', 50.00),
(487, 5, '2026-09-26', 'JAZ C04', 'Completed', 50.00),
(488, 5, '2026-09-26', 'MBOT 1 IM1 C05', 'Completed', 50.00),
(489, 5, '2026-09-26', 'MBOT JR C04', 'Completed', 50.00),
(490, 5, '2026-09-26', 'MBOT JR C06', 'Completed', 50.00),
(491, 5, '2026-09-26', 'MBOT JR C12', 'Completed', 50.00),
(492, 5, '2026-09-26', 'REV &ASSESSMENT', 'Completed', 50.00),
(493, 5, '2026-10-03', 'ADV C05', 'Completed', 50.00),
(494, 5, '2026-10-03', 'ADV C06', 'Completed', 50.00),
(495, 5, '2026-10-03', 'IM1 C01', 'Completed', 50.00),
(496, 5, '2026-10-03', 'IM1 C08', 'Completed', 50.00),
(497, 5, '2026-10-03', 'IM1 C09', 'Completed', 50.00),
(498, 5, '2026-10-03', 'IM1 C12', 'Completed', 50.00),
(499, 5, '2026-10-03', 'IM2 C06', 'Completed', 50.00),
(500, 5, '2026-10-03', 'IM2 C07', 'Completed', 50.00),
(501, 5, '2026-10-03', 'IM2 C08', 'Completed', 50.00),
(502, 5, '2026-10-03', 'IM2 C11', 'Completed', 50.00),
(503, 5, '2026-10-03', 'MBOT 1 IM1 C06', 'Completed', 50.00),
(504, 5, '2026-10-03', 'MBOT JR C01', 'Completed', 50.00),
(505, 5, '2026-10-03', 'MBOT JR C04', 'Completed', 50.00),
(506, 5, '2026-10-03', 'MBOT JR C05', 'Completed', 50.00),
(507, 5, '2026-10-03', 'REV &ASSESSMENT', 'Completed', 50.00),
(508, 5, '2026-10-03', 'SPR JAZ & DRONE C01', 'Completed', 50.00),
(509, 5, '2026-10-10', 'ADV C07', 'Completed', 50.00),
(510, 5, '2026-10-10', 'IM1 C09', 'Completed', 50.00),
(511, 5, '2026-10-10', 'IM1 C10', 'Completed', 50.00),
(512, 5, '2026-10-10', 'IM2 C02', 'Completed', 50.00),
(513, 5, '2026-10-10', 'IM2 C08', 'Completed', 50.00),
(514, 5, '2026-10-10', 'IM2 C09', 'Completed', 50.00),
(515, 5, '2026-10-10', 'IM2 C12', 'Completed', 50.00),
(516, 5, '2026-10-10', 'MBOT 1 IM1 C07', 'Completed', 50.00),
(517, 5, '2026-10-10', 'MBOT IM1 C01', 'Completed', 50.00),
(518, 5, '2026-10-10', 'MBOT JR C05', 'Completed', 50.00),
(519, 5, '2026-10-10', 'MBOT JR C07', 'Completed', 50.00),
(520, 5, '2026-10-10', 'REV &ASSESSMENT', 'Completed', 50.00),
(521, 5, '2026-10-10', 'SPACE C01', 'Completed', 50.00),
(522, 5, '2026-10-17', 'ADV C08', 'Completed', 50.00),
(523, 5, '2026-10-17', 'IM1 C10', 'Completed', 50.00),
(524, 5, '2026-10-17', 'IM1 C11', 'Completed', 50.00),
(525, 5, '2026-10-17', 'IM2 C03', 'Completed', 50.00),
(526, 5, '2026-10-17', 'IM2 C09', 'Completed', 50.00),
(527, 5, '2026-10-17', 'IM2 C10', 'Completed', 50.00),
(528, 5, '2026-10-17', 'MBOT 1 IM1 C08', 'Completed', 50.00),
(529, 5, '2026-10-17', 'MBOT IM1 C02', 'Completed', 50.00),
(530, 5, '2026-10-17', 'MBOT JR C06', 'Completed', 50.00),
(531, 5, '2026-10-17', 'MBOT JR C08', 'Completed', 50.00),
(532, 5, '2026-10-17', 'REV &ASSESSMENT', 'Completed', 50.00),
(533, 5, '2026-10-17', 'SPACE C02', 'Completed', 50.00),
(534, 5, '2026-10-17', 'SPR IM1 & IM2 C01', 'Completed', 50.00),
(535, 5, '2026-10-24', 'ADV C01', 'Completed', 50.00),
(536, 5, '2026-10-24', 'AI C01', 'Completed', 50.00),
(537, 5, '2026-10-24', 'IM1 C08', 'Completed', 50.00),
(538, 5, '2026-10-24', 'IM1 C11', 'Completed', 50.00),
(539, 5, '2026-10-24', 'IM1 C12', 'Completed', 50.00),
(540, 5, '2026-10-24', 'IM2 C04', 'Completed', 50.00),
(541, 5, '2026-10-24', 'IM2 C10', 'Completed', 50.00),
(542, 5, '2026-10-24', 'IM2 C11', 'Completed', 50.00),
(543, 5, '2026-10-24', 'MBOT 1 IM1 C09', 'Completed', 50.00),
(544, 5, '2026-10-24', 'MBOT IM1 C03', 'Completed', 50.00),
(545, 5, '2026-10-24', 'MBOT JR C07', 'Completed', 50.00),
(546, 5, '2026-10-24', 'MBOT JR C09', 'Completed', 50.00),
(547, 5, '2026-10-24', 'REV &ASSESSMENT', 'Completed', 50.00),
(548, 5, '2026-11-07', 'ADV C02', 'Scheduled', 50.00),
(549, 5, '2026-11-07', 'AI C02', 'Scheduled', 50.00),
(550, 5, '2026-11-07', 'IM1 C12', 'Scheduled', 50.00),
(551, 5, '2026-11-07', 'IM2 C05', 'Scheduled', 50.00),
(552, 5, '2026-11-07', 'IM2 C11', 'Scheduled', 50.00),
(553, 5, '2026-11-07', 'IM2 C12', 'Scheduled', 50.00),
(554, 5, '2026-11-07', 'JAZ C01', 'Scheduled', 50.00),
(555, 5, '2026-11-07', 'MBOT 1 IM1 C10', 'Scheduled', 50.00),
(556, 5, '2026-11-07', 'MBOT IM1 C04', 'Scheduled', 50.00),
(557, 5, '2026-11-07', 'MBOT JR C08', 'Scheduled', 50.00),
(558, 5, '2026-11-07', 'MBOT JR C10', 'Scheduled', 50.00),
(559, 5, '2026-11-14', 'ADV C03', 'Scheduled', 50.00),
(560, 5, '2026-11-14', 'IM2 C06', 'Scheduled', 50.00),
(561, 5, '2026-11-14', 'IM2 C12', 'Scheduled', 50.00),
(562, 5, '2026-11-14', 'IOT C01', 'Scheduled', 50.00),
(563, 5, '2026-11-14', 'JAZ C02', 'Scheduled', 50.00),
(564, 5, '2026-11-14', 'MBOT 1 IM1 C11', 'Scheduled', 50.00),
(565, 5, '2026-11-14', 'MBOT IM1 C05', 'Scheduled', 50.00),
(566, 5, '2026-11-14', 'MBOT JR C09', 'Scheduled', 50.00),
(567, 5, '2026-11-14', 'MBOT JR C11', 'Scheduled', 50.00),
(568, 5, '2026-11-14', 'REV', 'Scheduled', 50.00),
(569, 5, '2026-11-14', 'REV IM2', 'Scheduled', 50.00),
(570, 5, '2026-11-21', 'ADV C01', 'Scheduled', 50.00),
(571, 5, '2026-11-21', 'ADV C04', 'Scheduled', 50.00),
(572, 5, '2026-11-21', 'IM2 C01', 'Scheduled', 50.00),
(573, 5, '2026-11-21', 'IM2 C07', 'Scheduled', 50.00),
(574, 5, '2026-11-21', 'IOT C02', 'Scheduled', 50.00),
(575, 5, '2026-11-21', 'JAZ C03', 'Scheduled', 50.00),
(576, 5, '2026-11-21', 'MBOT 1 IM1 C12', 'Scheduled', 50.00),
(577, 5, '2026-11-21', 'MBOT IM1 C06', 'Scheduled', 50.00),
(578, 5, '2026-11-21', 'MBOT JR C10', 'Scheduled', 50.00),
(579, 5, '2026-11-21', 'MBOT JR C12', 'Scheduled', 50.00),
(580, 5, '2026-11-21', 'REV', 'Scheduled', 50.00),
(581, 5, '2026-11-28', 'ADV C01', 'Scheduled', 50.00),
(582, 5, '2026-11-28', 'ADV C02', 'Scheduled', 50.00),
(583, 5, '2026-11-28', 'ADV C05', 'Scheduled', 50.00),
(584, 5, '2026-11-28', 'DRONE C01', 'Scheduled', 50.00),
(585, 5, '2026-11-28', 'IM2 C02', 'Scheduled', 50.00),
(586, 5, '2026-11-28', 'IM2 C08', 'Scheduled', 50.00),
(587, 5, '2026-11-28', 'JAZ C04', 'Scheduled', 50.00),
(588, 5, '2026-11-28', 'MBOT IM1 C07', 'Scheduled', 50.00),
(589, 5, '2026-11-28', 'MBOT JR C11', 'Scheduled', 50.00),
(590, 5, '2026-11-28', 'REV', 'Scheduled', 50.00),
(591, 5, '2026-11-28', 'REV IM1', 'Scheduled', 50.00),
(592, 5, '2026-12-05', 'ADV C01', 'Scheduled', 50.00),
(593, 5, '2026-12-05', 'ADV C03', 'Scheduled', 50.00),
(594, 5, '2026-12-05', 'ADV C06', 'Scheduled', 50.00),
(595, 5, '2026-12-05', 'DRONE C02', 'Scheduled', 50.00),
(596, 5, '2026-12-05', 'IM1 C01', 'Scheduled', 50.00),
(597, 5, '2026-12-05', 'IM2 C03', 'Scheduled', 50.00),
(598, 5, '2026-12-05', 'IM2 C09', 'Scheduled', 50.00),
(599, 5, '2026-12-05', 'JAZ C05', 'Scheduled', 50.00),
(600, 5, '2026-12-05', 'MBOT IM1 C08', 'Scheduled', 50.00),
(601, 5, '2026-12-05', 'MBOT JR C12', 'Scheduled', 50.00),
(602, 5, '2026-12-12', 'ADV C02', 'Scheduled', 50.00),
(603, 5, '2026-12-12', 'ADV C04', 'Scheduled', 50.00),
(604, 5, '2026-12-12', 'ADV C07', 'Scheduled', 50.00),
(605, 5, '2026-12-12', 'IM1 C02', 'Scheduled', 50.00),
(606, 5, '2026-12-12', 'IM2 C04', 'Scheduled', 50.00),
(607, 5, '2026-12-12', 'IM2 C10', 'Scheduled', 50.00),
(608, 5, '2026-12-12', 'JAZ C06', 'Scheduled', 50.00),
(609, 5, '2026-12-12', 'MBOT IM1 C09', 'Scheduled', 50.00),
(610, 5, '2026-12-12', 'REV &ASSESSMENT', 'Scheduled', 50.00),
(611, 5, '2026-12-19', 'ADV C03', 'Scheduled', 50.00),
(612, 5, '2026-12-19', 'ADV C05', 'Scheduled', 50.00),
(613, 5, '2026-12-19', 'ADV C08', 'Scheduled', 50.00),
(614, 5, '2026-12-19', 'IM1 C03', 'Scheduled', 50.00),
(615, 5, '2026-12-19', 'IM2 C05', 'Scheduled', 50.00),
(616, 5, '2026-12-19', 'IM2 C11', 'Scheduled', 50.00),
(617, 5, '2026-12-19', 'JAZ C07', 'Scheduled', 50.00),
(618, 5, '2026-12-19', 'MBOT IM1 C10', 'Scheduled', 50.00),
(619, 5, '2026-12-26', 'ADV C04', 'Scheduled', 50.00),
(620, 5, '2026-12-26', 'ADV C06', 'Scheduled', 50.00),
(621, 5, '2026-12-26', 'IM1 C04', 'Scheduled', 50.00),
(622, 5, '2026-12-26', 'IM2 C06', 'Scheduled', 50.00),
(623, 5, '2026-12-26', 'IM2 C12', 'Scheduled', 50.00),
(624, 5, '2026-12-26', 'JAZ C08', 'Scheduled', 50.00),
(625, 5, '2026-12-26', 'MBOT IM1 C11', 'Scheduled', 50.00),
(626, 5, '2026-12-26', 'REV &ASSESSMENT', 'Scheduled', 50.00);

-- --------------------------------------------------------

--
-- Table structure for table `enrollment`
--

CREATE TABLE `enrollment` (
  `enrollment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `enrollment_date` date NOT NULL,
  `status` enum('Active','Inactive','Completed') DEFAULT 'Active'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `enrollment`
--

INSERT INTO `enrollment` (`enrollment_id`, `student_id`, `class_id`, `enrollment_date`, `status`) VALUES
(1, 1, 1, '2026-01-07', 'Active'),
(2, 2, 1, '2026-01-07', 'Active'),
(3, 3, 1, '2026-01-07', 'Active'),
(4, 4, 1, '2026-01-07', 'Active'),
(5, 5, 1, '2026-01-07', 'Active'),
(6, 6, 1, '2026-01-07', 'Active'),
(7, 7, 1, '2026-01-07', 'Active'),
(8, 8, 1, '2026-01-07', 'Active'),
(9, 9, 1, '2026-01-07', 'Active'),
(10, 10, 1, '2026-01-07', 'Active'),
(11, 11, 1, '2026-01-07', 'Active'),
(12, 12, 1, '2026-01-07', 'Active'),
(13, 13, 1, '2026-01-07', 'Active'),
(14, 14, 1, '2026-01-07', 'Active'),
(15, 15, 1, '2026-01-07', 'Active'),
(16, 16, 1, '2026-10-03', 'Active'),
(17, 17, 1, '2026-01-07', 'Active'),
(18, 18, 1, '2026-01-07', 'Active'),
(19, 19, 2, '2026-02-05', 'Active'),
(20, 20, 2, '2026-02-05', 'Active'),
(21, 21, 2, '2026-02-05', 'Active'),
(22, 22, 2, '2026-02-05', 'Active'),
(23, 23, 2, '2026-02-05', 'Active'),
(24, 24, 2, '2026-02-05', 'Active'),
(25, 25, 2, '2026-02-12', 'Active'),
(26, 26, 2, '2026-03-26', 'Active'),
(27, 27, 2, '2026-03-12', 'Active'),
(28, 28, 2, '2026-04-02', 'Active'),
(29, 29, 2, '2026-03-26', 'Active'),
(30, 30, 2, '2026-04-16', 'Active'),
(31, 31, 2, '2026-04-23', 'Active'),
(32, 32, 2, '2026-06-04', 'Active'),
(33, 33, 2, '2026-02-05', 'Active'),
(34, 34, 2, '2026-08-06', 'Active');

-- --------------------------------------------------------

--
-- Table structure for table `learning_material`
--

CREATE TABLE `learning_material` (
  `material_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `material_title` varchar(200) NOT NULL,
  `file_url` varchar(500) NOT NULL,
  `description` text DEFAULT NULL,
  `upload_date` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `module`
--

CREATE TABLE `module` (
  `module_id` int(11) NOT NULL,
  `module_code` varchar(50) NOT NULL,
  `module_name` varchar(150) NOT NULL,
  `module_category` varchar(100) DEFAULT NULL,
  `level` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `organization`
--

CREATE TABLE `organization` (
  `organization_id` int(11) NOT NULL,
  `organization_name` varchar(200) NOT NULL,
  `organization_type` varchar(100) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `ctc_number` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `parent`
--

CREATE TABLE `parent` (
  `parent_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `phone_number` varchar(20) DEFAULT NULL,
  `parent_name` varchar(150) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `parent`
--

INSERT INTO `parent` (`parent_id`, `user_id`, `phone_number`, `parent_name`) VALUES
(1, 2, '0162531327', 'Aimi Nadia\r\n'),
(2, 3, '0195808980', 'AHMAD NAZRI BIN JUSOH'),
(3, 4, '0193181374', 'Faira Hanim Razali'),
(4, 5, '0164412134', 'AMY AZIRA BINTI HAMIS'),
(5, 6, '0145333554', 'NORFARAHIN IZZATI BINTI YUSOF @ MOHD NOR'),
(6, 7, '0192984997', 'NORAZLINA BINTI IBRAHIM'),
(7, 8, '0196588965', 'AHMAD HAMIDI BIN SALLEH'),
(8, 9, '0122393742', 'Nur Hafizah Binti Md Salleh'),
(9, 10, '0137199537', 'Nabiha Binti Mohd Mashut'),
(10, 11, '0173288424', 'Khalilah Tajuddin'),
(11, 12, '0176241488', 'Hannah Sarah'),
(12, 13, '0102989002', 'NOR HANIZAH BINTI ABDUL GAPAL'),
(13, 14, '0133641119', 'Noranefa Abu Kasim'),
(14, 15, '0177763145', 'JOHANNA BINTI MOHD YASIN\r\n'),
(15, 16, '0139805752', 'Sheikh Ibrahim Bin Sheikh Mohamed KharulAsrah'),
(16, 17, '0132048341', 'Rafizah Mat Shah'),
(17, 18, '0125210667', 'Faizatul Izzati Bt Mohd Osmani'),
(18, 19, '0175515800', 'MUHAMMAD ASHRAF BIN ARSAD'),
(19, 20, '01110106309', 'SAIFUL AMALUDDIN\r\n'),
(20, 21, '0148353773', 'Nabil Aiman Bin Hasim\r\n'),
(21, 22, '0166706607', 'THARUMASELAN\r\n'),
(22, 23, '01129552928', 'RABIAHTON ADAWIAH BINTI ROSLI'),
(23, 24, '0137114013', 'NUR ATIQAH BT MUHAMMAD NAZMI'),
(24, 25, '0136833014', 'HANIS IZZAT ALI SHUBRA MULLISI'),
(25, 26, '0123088428', 'SERI MUNAWIR'),
(26, 27, '0194774267', 'ROSE SHAMSIAH SAMSUDIN'),
(27, 28, '0194041481', 'SHAHRUL BARIAH ABD. RAHIM'),
(28, 29, '0122166712', 'HAMISAH ISHAK'),
(29, 30, '0162727786', 'AHMAD RAFIDI LEBAI KAMALUDIN'),
(30, 31, '01132911708', 'MUHAMMAD FAIQ NABIL BIN RAMLI'),
(31, 32, '0126217525', 'SHARIFAH MARYAM BINTI SYED MOHD SYAHMI'),
(32, 33, '0196706369', 'VEERAMOGAN'),
(33, 34, '0179349552', 'LAILATUWALA BINTI MOHAMED LAZIM'),
(34, 35, '0124839662', 'ZHAFARULLAH BIN KHAIRUL HISYAM'),
(35, 137, '0176120210', 'ADI SADIKIN\r\n'),
(36, 138, '0173434814', 'Norliza Ismail'),
(37, 139, '0139278005', 'Norita Binti Che Said\r\n'),
(38, 140, '01165532003', 'ZAINORA BINTI SOLAMAN\r\n'),
(39, 141, '60139115210', 'ROHAIZA BINTI REMLI\r\n'),
(40, 142, '0197790079', 'Abd Fathul Hakim Bin Zulkifli\r\n');

-- --------------------------------------------------------

--
-- Table structure for table `payment`
--

CREATE TABLE `payment` (
  `payment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_date` date DEFAULT NULL,
  `payment_status` enum('Pending','Paid','Failed','Refunded') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `recording`
--

CREATE TABLE `recording` (
  `recording_id` int(11) NOT NULL,
  `session_id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `recording_url` varchar(500) NOT NULL,
  `title` varchar(200) NOT NULL,
  `date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `reportcard_softskill`
--

CREATE TABLE `reportcard_softskill` (
  `report_skill_id` int(11) NOT NULL,
  `reportcard_id` int(11) NOT NULL,
  `soft_skill_id` int(11) NOT NULL,
  `score` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_card`
--

CREATE TABLE `report_card` (
  `reportcard_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `class_id` int(11) NOT NULL,
  `academic_term` varchar(50) DEFAULT NULL,
  `academic_year` year(4) NOT NULL,
  `attendance_score` decimal(5,2) DEFAULT NULL,
  `skills_score` decimal(5,2) DEFAULT NULL,
  `softskills_score` decimal(5,2) DEFAULT NULL,
  `portal_assessment_score` decimal(5,2) DEFAULT NULL,
  `overall_score` decimal(5,2) DEFAULT NULL,
  `grade` varchar(5) DEFAULT NULL,
  `report_date` date DEFAULT NULL,
  `comment` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `report_card_topic`
--

CREATE TABLE `report_card_topic` (
  `report_topic_id` int(11) NOT NULL,
  `reportcard_id` int(11) NOT NULL,
  `topic_name` varchar(200) NOT NULL,
  `achievement_score` decimal(5,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `result_assessment`
--

CREATE TABLE `result_assessment` (
  `result_id` int(11) NOT NULL,
  `assessment_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `score` decimal(8,2) DEFAULT NULL,
  `percentage` decimal(5,2) DEFAULT NULL,
  `status` enum('Not Started','In Progress','Completed') DEFAULT 'Not Started',
  `completed_date` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `session_material`
--

CREATE TABLE `session_material` (
  `session_id` int(11) NOT NULL,
  `material_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `soft_skill`
--

CREATE TABLE `soft_skill` (
  `soft_skill_id` int(11) NOT NULL,
  `skill_name` varchar(150) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `student`
--

CREATE TABLE `student` (
  `student_id` int(11) NOT NULL,
  `parent_id` int(11) NOT NULL,
  `organization_id` int(11) DEFAULT NULL,
  `student_name` varchar(150) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `ic_number` varchar(20) DEFAULT NULL,
  `school_name` varchar(200) DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `student`
--

INSERT INTO `student` (`student_id`, `parent_id`, `organization_id`, `student_name`, `age`, `ic_number`, `school_name`, `gender`) VALUES
(1, 1, NULL, 'Syeda Batrisyia Shah Bokharyc\r\n', 9, '161116102998', NULL, 'girl'),
(2, 2, NULL, 'Ahmad Ilham Amsyar Bin Ahmad Nazri', 9, '160304020557', 'SK Pendang\r\n', 'boy'),
(3, 3, NULL, 'Muhammad Syuaib Bin Azlan Shah', 12, '140805100477', 'SK NILAI IMPIAN', 'boy'),
(4, 4, NULL, 'ARIANA RAUDHAH BINTI MOHD REZA', 11, '150213040094', 'SK Bukit Senggeh', 'girl'),
(5, 5, NULL, 'MUHAMMAD HARITH ZIQRI BIN MOHD KHAIRUL NA\'IM', 9, '170729110629', 'SK SRI TEBRAU', 'boy'),
(6, 6, NULL, 'NUR ZAHIRAH BINTI MOHD ZAIDI', 12, '141106060204', 'SK SURA', 'girl'),
(7, 7, NULL, 'AHMAD FA\'IZ BIN AHMAD HAMIDI', 11, '150716030779', 'SK KERATONG DUA', 'boy'),
(8, 8, NULL, 'Muhammad Hafey Bin Muhammad Hafiz', 14, '120606140697', 'SMK Putrajaya Presint 18(1)', 'boy'),
(9, 9, NULL, 'Muhammad Naqib Bin Muhammad Zulfadli', 11, '150228010453', NULL, 'boy'),
(10, 10, NULL, 'ABDUL MUHAIMIN BIN ABDUL AZIM', 15, '110208101159', 'SEKOLAH ANAK MALAYSIA', 'boy'),
(11, 11, NULL, 'AMIR ARSYAD BIN MUHAMMAD AZRI', 9, '170911040487', 'SRIBEST', 'boy'),
(12, 12, NULL, 'MUHAMMAD IRFAN ZAFRAN BIN MOHD MUTTAQIN', 11, '150725140451', NULL, 'boy'),
(13, 13, NULL, 'NAFIS SYAHMI BIN SALMAN', 10, '160506102083', NULL, 'boy'),
(14, 14, NULL, 'MUHAMAD ADYAN BIN MUHAMAD SHUKRI', 13, '131101103579', 'SK SUNGAI ULAR', 'boy'),
(15, 15, NULL, 'Sheikh Ibrahim Bin Sheikh Mohamed KharulAsrah', 10, '161116060089', 'SK Air Putih, Kuantan', 'boy'),
(16, 16, NULL, 'Muhammad Uwais Arifuddin Bin Mohd Khairuddin', 12, '140618160087', 'SKPP8(2)', 'boy'),
(17, 17, NULL, 'Mus\'ab Umair Bin Muhammad', 9, '170321110445', NULL, 'boy'),
(18, 18, NULL, 'MARIYA AL HAYA BINTI MUHAMMAD ASHRAF', 12, '140313100200 ', 'SK SEKSYEN 2 BANDAR KINRARA', 'girl'),
(19, 19, NULL, 'SUFI AHMAD DANIYAL', 10, '160920011101', NULL, 'boy'),
(20, 20, NULL, 'Thoriq Ziyad Bin Nabil Aiman', 12, '140720030721', 'SJK(C) CHONG HWA WAKAF TAPAI', 'boy'),
(21, 21, NULL, 'SANJIV RUDDREN A/L THARUMASELAN', 9, '170818070171', 'SEKOLAH KEBANGSAAN WAWASAN S2', 'boy'),
(22, 22, NULL, 'NUR THAQIFAH MURFIQAH BINTI MOHD HANIFF', 11, '150621102224', 'SK RAJA PEREMPUAN', 'girl'),
(23, 23, NULL, 'Hanis Safiya Binti Hanis Izzat', 11, '150313020058', 'SK Desa Tun Hussein Onn', 'girl'),
(24, 24, NULL, 'HASYA IRDINA BINTI HANIS IZZAT', 9, '170924020418', 'SK KEMENTAH', 'girl'),
(25, 25, NULL, 'MUHAMMAD YUSOF ALEXANDER BIN MUHAMMAD TAQIYUDDIN', 8, '180801103087', 'SRI UCSI SJ', 'boy'),
(26, 26, NULL, 'MUHAMMAD FIRDAUS AKRAM MOHD FADZLI', 19, '071016021091', NULL, 'boy'),
(27, 27, NULL, 'Muhammad Zakwan Bin Sazali', 10, '161107-10-2611', NULL, 'boy'),
(28, 28, NULL, 'Muhammad Bin Arief', 10, '160423140041', 'SK Bukit Damansara', 'boy'),
(29, 29, NULL, 'Aidil Haziq bin Ahmad Rafidi', 12, '140804140055', 'SK Seri Bintang Selatan', 'boy'),
(30, 30, NULL, 'SAIF AL-ISLAM BIN MUHAMMAD FAIQ NABIL', 13, '130616880039', 'SMK SEREMBAN 2', 'boy'),
(31, 31, NULL, 'SHARIFAH MARYAM BINTI SYED MOHD SYAHMI', 11, '150508060340', 'SEKOLAH KEBANGSAAN SUNGAI TALAM, KUANTAN, PAHANG', 'girl'),
(32, 32, NULL, 'Datchanaamorthy', 14, '120502021159', 'SMK Air Merah Kulim', 'boy'),
(33, 33, NULL, 'MUHAMMAD ADAM HAZIQ BIN MOHAMAD NAJMAN', 12, '140422030061', 'SK INDERA MAHKOTA', 'boy'),
(34, 34, NULL, 'Zhafarullah Bin Khairul Hisyam', 10, '160118100051', NULL, 'boy'),
(35, 35, NULL, 'AHMAD ILHAM HASSAN BIN ADI SADIKIN\r\n', 16, '100724160017 ', 'KV TAIPING\r\n', 'boy'),
(36, 36, NULL, 'Fatihah Binti Muhamad Faizal\r\n', 11, '150307020190', 'SK Pekan Sipitang\r\n', 'girl'),
(37, 37, NULL, 'Muhammad Adam Alif Bin Mohd Azrul\r\n', 11, '151002070343', NULL, 'boy'),
(38, 38, NULL, 'MUHAMMAD AL FATEH BIN MOHD AMIRUL MUKMIN\r\n', 14, '120831100835 ', 'SMK SETAPAK INDAH\r\n', 'boy'),
(39, 39, NULL, 'AHMAD HAKEEM BIN MOHD NAZRUL HISHAM\r\n', 12, '140217110345 ', 'SK Sura', 'boy'),
(40, 39, NULL, 'AHMAD HAAFEZ BIN MOHD NAZRUL HISHAM\r\n', 10, '160802110061', NULL, 'boy'),
(41, 28, NULL, 'Humaira Binti Arief\r\n', 11, '150208140170', 'SK Bukit Damansara\r\n', 'girl'),
(42, 40, NULL, 'Faathir Ilman Bin Abd Fathul Hakim\r\n', 11, '150518010259 ', 'SK KOTA RAJA\r\n', 'boy');

-- --------------------------------------------------------

--
-- Table structure for table `student_blooket`
--

CREATE TABLE `student_blooket` (
  `stu_blooket_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `blooket_id` int(11) NOT NULL,
  `status` enum('Not Started','In Progress','Completed') DEFAULT 'Not Started',
  `date_played` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `user`
--

CREATE TABLE `user` (
  `user_id` int(11) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` enum('PARENT','ADMIN') NOT NULL,
  `username` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user`
--

INSERT INTO `user` (`user_id`, `password`, `email`, `role`, `username`) VALUES
(1, 'jazarian123@', 'jazroteam@gmail.com', 'ADMIN', 'admin_jazro'),
(2, 'parents123jazro', 'aiminadia@hotmail.com', 'PARENT', ''),
(3, 'parents123jazro', 'nazrey1912@gmail.com', 'PARENT', ''),
(4, 'parents123jazro', 'faira_hanim@yahoo.com', 'PARENT', ''),
(5, 'parents123jazro', 'amyazira1312@gmail.com\r\n', 'PARENT', ''),
(6, 'parents123jazro', 'zati9191@gmail.com', 'PARENT', ''),
(7, 'parents123jazro', 'poplady82.ni.ni@gmail.com\r\n', 'PARENT', ''),
(8, 'parents123jazro', 'gr8th1nk3r@gmail.com', 'PARENT', ''),
(9, 'parents123jazro', 'hafizah_2506@yahoo.com.my', 'PARENT', ''),
(10, 'parents123jazro', 'nabiha.mashut@gmail.com', 'PARENT', ''),
(11, 'parents123jazro', 'aeyla88@gmail.com', 'PARENT', ''),
(12, 'parents123jazro', 'hsarah.msg@gmail.com', 'PARENT', ''),
(13, 'parents123jazro', 'norhanizah.dr@gmail.com', 'PARENT', ''),
(14, 'parents123jazro', 'eyfa80@gmail.com', 'PARENT', ''),
(15, 'parents123jazro', 'johanna8287@gmail.com', 'PARENT', ''),
(16, 'parents123jazro', 'adilla.razak16@gmail.com', 'PARENT', ''),
(17, 'parents123jazro', 'alongtoot@gmail.com', 'PARENT', ''),
(18, 'parents123jazro', 'fizzati.mo@gmail.com', 'PARENT', ''),
(19, 'parents123jazro', 'mech_astech@yahoo.com', 'PARENT', ''),
(20, 'parents123jazro', 'saifulamaluddinepulroy@gmail.com', 'PARENT', ''),
(21, 'parents123jazro', 'drx.nabil@hotmail.com', 'PARENT', ''),
(22, 'parents123jazro', 'tharumaselan7861@gmail.com', 'PARENT', ''),
(23, 'parents123jazro', 'atonerosli@yahoo.com', 'PARENT', ''),
(24, 'parents123jazro', 'atiqahnazmi@gmail.com', 'PARENT', ''),
(25, 'parents123jazro', 'izzatqah87@gmail.com', 'PARENT', ''),
(26, 'parents123jazro', 'serimunawir@gmail.com', 'PARENT', ''),
(27, 'parents123jazro', 'roseshamsiah1967@yahoo.com', 'PARENT', ''),
(28, 'parents123jazro', 'sha.abdrahim@gmail.com', 'PARENT', ''),
(29, 'parents123jazro', 'hhishak@gmail.com', 'PARENT', ''),
(30, 'parents123jazro', 'Nyet_937@yahoo.com', 'PARENT', ''),
(31, 'parents123jazro', 'fnabil.ramli@gmail.com', 'PARENT', ''),
(32, 'parents123jazro', 'elina@iium.edu.my', 'PARENT', ''),
(33, 'parents123jazro', 'veeramogan.selvarajoo@firstsolar.com', 'PARENT', ''),
(34, 'parents123jazro', 'laila_lazim@yahoo.com', 'PARENT', ''),
(35, 'parents123jazro', 'zhafarullahkh@gmail.com', 'PARENT', ''),
(137, 'parents123jazro', 'h0701330@gmail.com', 'PARENT', ''),
(138, 'parents123jazro', 'norlizaismail11@gmail.com', 'PARENT', ''),
(139, 'parents123jazro', 'norita.azrul@gmail.com', 'PARENT', ''),
(140, 'parents123jazro', 'zainora.sj@gmail.com', 'PARENT', ''),
(141, 'parents123jazro', 'ejaeza1009@gmail.com', 'PARENT', ''),
(142, 'parents123jazro', 'fathulhakim13@gmail.com', 'PARENT', '');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `assessment`
--
ALTER TABLE `assessment`
  ADD PRIMARY KEY (`assessment_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `module_id` (`module_id`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD UNIQUE KEY `session_id` (`session_id`,`student_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `blooket`
--
ALTER TABLE `blooket`
  ADD PRIMARY KEY (`blooket_id`);

--
-- Indexes for table `branch`
--
ALTER TABLE `branch`
  ADD PRIMARY KEY (`branch_id`),
  ADD UNIQUE KEY `branch_code` (`branch_code`);

--
-- Indexes for table `certificate`
--
ALTER TABLE `certificate`
  ADD PRIMARY KEY (`certificate_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `class_id` (`class_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `classes`
--
ALTER TABLE `classes`
  ADD PRIMARY KEY (`class_id`),
  ADD UNIQUE KEY `class_code` (`class_code`),
  ADD KEY `branch_id` (`branch_id`),
  ADD KEY `organization_id` (`organization_id`);

--
-- Indexes for table `class_session`
--
ALTER TABLE `class_session`
  ADD PRIMARY KEY (`session_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD PRIMARY KEY (`enrollment_id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`class_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `learning_material`
--
ALTER TABLE `learning_material`
  ADD PRIMARY KEY (`material_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `module`
--
ALTER TABLE `module`
  ADD PRIMARY KEY (`module_id`),
  ADD UNIQUE KEY `module_code` (`module_code`);

--
-- Indexes for table `organization`
--
ALTER TABLE `organization`
  ADD PRIMARY KEY (`organization_id`);

--
-- Indexes for table `parent`
--
ALTER TABLE `parent`
  ADD PRIMARY KEY (`parent_id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `payment`
--
ALTER TABLE `payment`
  ADD PRIMARY KEY (`payment_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `session_id` (`session_id`);

--
-- Indexes for table `recording`
--
ALTER TABLE `recording`
  ADD PRIMARY KEY (`recording_id`),
  ADD KEY `session_id` (`session_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `reportcard_softskill`
--
ALTER TABLE `reportcard_softskill`
  ADD PRIMARY KEY (`report_skill_id`),
  ADD UNIQUE KEY `reportcard_id` (`reportcard_id`,`soft_skill_id`),
  ADD KEY `soft_skill_id` (`soft_skill_id`);

--
-- Indexes for table `report_card`
--
ALTER TABLE `report_card`
  ADD PRIMARY KEY (`reportcard_id`),
  ADD KEY `student_id` (`student_id`),
  ADD KEY `class_id` (`class_id`);

--
-- Indexes for table `report_card_topic`
--
ALTER TABLE `report_card_topic`
  ADD PRIMARY KEY (`report_topic_id`),
  ADD KEY `reportcard_id` (`reportcard_id`);

--
-- Indexes for table `result_assessment`
--
ALTER TABLE `result_assessment`
  ADD PRIMARY KEY (`result_id`),
  ADD UNIQUE KEY `assessment_id` (`assessment_id`,`student_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `session_material`
--
ALTER TABLE `session_material`
  ADD PRIMARY KEY (`session_id`,`material_id`),
  ADD KEY `material_id` (`material_id`);

--
-- Indexes for table `soft_skill`
--
ALTER TABLE `soft_skill`
  ADD PRIMARY KEY (`soft_skill_id`);

--
-- Indexes for table `student`
--
ALTER TABLE `student`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `ic_number` (`ic_number`),
  ADD KEY `parent_id` (`parent_id`),
  ADD KEY `organization_id` (`organization_id`);

--
-- Indexes for table `student_blooket`
--
ALTER TABLE `student_blooket`
  ADD PRIMARY KEY (`stu_blooket_id`),
  ADD UNIQUE KEY `student_id` (`student_id`,`blooket_id`),
  ADD KEY `blooket_id` (`blooket_id`);

--
-- Indexes for table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `admin_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `assessment`
--
ALTER TABLE `assessment`
  MODIFY `assessment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2686;

--
-- AUTO_INCREMENT for table `blooket`
--
ALTER TABLE `blooket`
  MODIFY `blooket_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `branch`
--
ALTER TABLE `branch`
  MODIFY `branch_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `certificate`
--
ALTER TABLE `certificate`
  MODIFY `certificate_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `classes`
--
ALTER TABLE `classes`
  MODIFY `class_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `class_session`
--
ALTER TABLE `class_session`
  MODIFY `session_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=627;

--
-- AUTO_INCREMENT for table `enrollment`
--
ALTER TABLE `enrollment`
  MODIFY `enrollment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `learning_material`
--
ALTER TABLE `learning_material`
  MODIFY `material_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `module`
--
ALTER TABLE `module`
  MODIFY `module_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `organization`
--
ALTER TABLE `organization`
  MODIFY `organization_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `parent`
--
ALTER TABLE `parent`
  MODIFY `parent_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `payment`
--
ALTER TABLE `payment`
  MODIFY `payment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `recording`
--
ALTER TABLE `recording`
  MODIFY `recording_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `reportcard_softskill`
--
ALTER TABLE `reportcard_softskill`
  MODIFY `report_skill_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_card`
--
ALTER TABLE `report_card`
  MODIFY `reportcard_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `report_card_topic`
--
ALTER TABLE `report_card_topic`
  MODIFY `report_topic_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `result_assessment`
--
ALTER TABLE `result_assessment`
  MODIFY `result_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `soft_skill`
--
ALTER TABLE `soft_skill`
  MODIFY `soft_skill_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `student`
--
ALTER TABLE `student`
  MODIFY `student_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `student_blooket`
--
ALTER TABLE `student_blooket`
  MODIFY `stu_blooket_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `user`
--
ALTER TABLE `user`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=143;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `admin`
--
ALTER TABLE `admin`
  ADD CONSTRAINT `admin_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `assessment`
--
ALTER TABLE `assessment`
  ADD CONSTRAINT `assessment_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
  ADD CONSTRAINT `assessment_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`),
  ADD CONSTRAINT `assessment_ibfk_3` FOREIGN KEY (`module_id`) REFERENCES `module` (`module_id`);

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `class_session` (`session_id`),
  ADD CONSTRAINT `attendance_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  ADD CONSTRAINT `attendance_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `certificate`
--
ALTER TABLE `certificate`
  ADD CONSTRAINT `certificate_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  ADD CONSTRAINT `certificate_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`),
  ADD CONSTRAINT `certificate_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `classes`
--
ALTER TABLE `classes`
  ADD CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branch` (`branch_id`),
  ADD CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`organization_id`) REFERENCES `organization` (`organization_id`);

--
-- Constraints for table `class_session`
--
ALTER TABLE `class_session`
  ADD CONSTRAINT `class_session_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`);

--
-- Constraints for table `enrollment`
--
ALTER TABLE `enrollment`
  ADD CONSTRAINT `enrollment_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  ADD CONSTRAINT `enrollment_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`);

--
-- Constraints for table `learning_material`
--
ALTER TABLE `learning_material`
  ADD CONSTRAINT `learning_material_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `parent`
--
ALTER TABLE `parent`
  ADD CONSTRAINT `parent_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`);

--
-- Constraints for table `payment`
--
ALTER TABLE `payment`
  ADD CONSTRAINT `payment_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  ADD CONSTRAINT `payment_ibfk_2` FOREIGN KEY (`session_id`) REFERENCES `class_session` (`session_id`);

--
-- Constraints for table `recording`
--
ALTER TABLE `recording`
  ADD CONSTRAINT `recording_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `class_session` (`session_id`),
  ADD CONSTRAINT `recording_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admin` (`admin_id`);

--
-- Constraints for table `reportcard_softskill`
--
ALTER TABLE `reportcard_softskill`
  ADD CONSTRAINT `reportcard_softskill_ibfk_1` FOREIGN KEY (`reportcard_id`) REFERENCES `report_card` (`reportcard_id`),
  ADD CONSTRAINT `reportcard_softskill_ibfk_2` FOREIGN KEY (`soft_skill_id`) REFERENCES `soft_skill` (`soft_skill_id`);

--
-- Constraints for table `report_card`
--
ALTER TABLE `report_card`
  ADD CONSTRAINT `report_card_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  ADD CONSTRAINT `report_card_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `classes` (`class_id`);

--
-- Constraints for table `report_card_topic`
--
ALTER TABLE `report_card_topic`
  ADD CONSTRAINT `report_card_topic_ibfk_1` FOREIGN KEY (`reportcard_id`) REFERENCES `report_card` (`reportcard_id`);

--
-- Constraints for table `result_assessment`
--
ALTER TABLE `result_assessment`
  ADD CONSTRAINT `result_assessment_ibfk_1` FOREIGN KEY (`assessment_id`) REFERENCES `assessment` (`assessment_id`),
  ADD CONSTRAINT `result_assessment_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`);

--
-- Constraints for table `session_material`
--
ALTER TABLE `session_material`
  ADD CONSTRAINT `session_material_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `class_session` (`session_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `session_material_ibfk_2` FOREIGN KEY (`material_id`) REFERENCES `learning_material` (`material_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `student`
--
ALTER TABLE `student`
  ADD CONSTRAINT `student_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `parent` (`parent_id`),
  ADD CONSTRAINT `student_ibfk_2` FOREIGN KEY (`organization_id`) REFERENCES `organization` (`organization_id`);

--
-- Constraints for table `student_blooket`
--
ALTER TABLE `student_blooket`
  ADD CONSTRAINT `student_blooket_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `student` (`student_id`),
  ADD CONSTRAINT `student_blooket_ibfk_2` FOREIGN KEY (`blooket_id`) REFERENCES `blooket` (`blooket_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
