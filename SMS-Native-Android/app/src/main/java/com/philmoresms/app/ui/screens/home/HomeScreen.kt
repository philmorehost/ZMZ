package com.philmoresms.app.ui.screens.home

import androidx.compose.foundation.background
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.*
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.graphics.vector.ImageVector
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import com.philmoresms.app.network.BaseResponse
import com.philmoresms.app.network.ErrorUtils
import com.philmoresms.app.network.RetrofitClient
import com.philmoresms.app.ui.components.FooterBar
import com.philmoresms.app.ui.theme.*
import kotlinx.coroutines.launch
import retrofit2.HttpException
import retrofit2.await

class HomeViewModel : ViewModel() {
    var data by mutableStateOf<BaseResponse?>(null)
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)

    fun fetchSummary() {
        loading = true
        error = null
        viewModelScope.launch {
            try {
                data = RetrofitClient.apiService.getSummary().await()
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to load dashboard"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            } finally {
                loading = false
            }
        }
    }
}

data class HomeService(val label: String, val icon: ImageVector, val color: Color, val route: String)

@Composable
fun HomeScreen(navController: NavController) {
    val viewModel: HomeViewModel = viewModel()
    LaunchedEffect(Unit) { viewModel.fetchSummary() }
    val data = viewModel.data

    Scaffold(
        bottomBar = {
            FooterBar("Home") { label ->
                when (label) {
                    "Support" -> navController.navigate("support")
                    "SMS Report" -> navController.navigate("reports")
                    else -> navController.navigate("home")
                }
            }
        }
    ) { padding ->
        Column(
            modifier = Modifier
                .fillMaxSize()
                .padding(padding)
                .background(Background)
                .verticalScroll(rememberScrollState())
        ) {
            // Header
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 20.dp, vertical = 16.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Column {
                    Text("Good Day 👋", fontSize = 14.sp, color = TextSecondary)
                    Text(
                        data?.stats?.username ?: "User",
                        fontSize = 24.sp,
                        fontWeight = FontWeight.Bold,
                        color = TextPrimary
                    )
                }
                Surface(
                    modifier = Modifier.size(46.dp),
                    shape = CircleShape,
                    color = Primary,
                    shadowElevation = 4.dp,
                    onClick = { navController.navigate("profile") }
                ) {
                    Box(contentAlignment = Alignment.Center) {
                        Icon(Icons.Default.Person, contentDescription = "Profile", tint = Color.White)
                    }
                }
            }

            // Balance card
            Box(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(horizontal = 20.dp)
                    .height(170.dp)
                    .background(Primary, RoundedCornerShape(24.dp))
                    .padding(24.dp)
            ) {
                Column {
                    Text("TOTAL BALANCE", fontSize = 12.sp, color = Color.White.copy(alpha = 0.7f), fontWeight = FontWeight.Bold)
                    Text(
                        "₦${data?.stats?.balance ?: 0.0}",
                        fontSize = 30.sp,
                        fontWeight = FontWeight.ExtraBold,
                        color = Color.White,
                        modifier = Modifier.padding(top = 8.dp)
                    )
                    Text("Messages sent: ${data?.stats?.messagesSent ?: 0}", fontSize = 13.sp, color = Color.White.copy(alpha = 0.85f))
                }
                Button(
                    onClick = { navController.navigate("topup") },
                    modifier = Modifier.align(Alignment.BottomEnd),
                    colors = ButtonDefaults.buttonColors(containerColor = Color.White),
                    shape = RoundedCornerShape(12.dp),
                    contentPadding = PaddingValues(horizontal = 16.dp, vertical = 10.dp)
                ) {
                    Icon(Icons.Default.Add, contentDescription = null, tint = Primary, modifier = Modifier.size(18.dp))
                    Spacer(Modifier.width(6.dp))
                    Text("Top Up", color = Primary, fontWeight = FontWeight.Bold, fontSize = 14.sp)
                }
            }

            // Quick services
            Text("Quick Services", fontSize = 18.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(start = 20.dp, top = 28.dp, bottom = 16.dp))

            val services = listOf(
                HomeService("Bulk SMS", Icons.Default.Sms, SmsColor, "bulk_sms"),
                HomeService("Voice SMS", Icons.Default.RecordVoiceOver, VoiceColor, "voice_sms"),
                HomeService("OTP", Icons.Default.VpnKey, OtpColor, "otp"),
                HomeService("Sender ID", Icons.Default.Badge, GlobalColor, "sender_id")
            )
            Column(modifier = Modifier.padding(horizontal = 20.dp)) {
                services.chunked(2).forEach { row ->
                    Row(modifier = Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.spacedBy(16.dp)) {
                        row.forEach { service ->
                            ServiceCard(service, Modifier.weight(1f)) { navController.navigate(service.route) }
                        }
                    }
                    Spacer(Modifier.height(16.dp))
                }
            }

            // Recent transactions
            Row(
                modifier = Modifier
                    .fillMaxWidth()
                    .padding(start = 20.dp, end = 20.dp, top = 24.dp, bottom = 16.dp),
                horizontalArrangement = Arrangement.SpaceBetween,
                verticalAlignment = Alignment.CenterVertically
            ) {
                Text("Recent Transactions", fontSize = 18.sp, fontWeight = FontWeight.Bold)
                Text("History", color = Primary, fontWeight = FontWeight.Bold, fontSize = 14.sp, modifier = Modifier.clip(RoundedCornerShape(8.dp)).clickable { navController.navigate("sms_history") }.padding(4.dp))
            }

            Column(modifier = Modifier.padding(horizontal = 20.dp)) {
                data?.recent_transactions?.forEach { tx ->
                    TransactionRow(tx)
                }
                if (viewModel.loading) {
                    Box(Modifier.fillMaxWidth().padding(24.dp), contentAlignment = Alignment.Center) { CircularProgressIndicator() }
                }
            }
            Spacer(Modifier.height(24.dp))
        }
    }
}

@Composable
private fun ServiceCard(service: HomeService, modifier: Modifier = Modifier, onClick: () -> Unit) {
    Surface(
        modifier = modifier.height(100.dp),
        shape = RoundedCornerShape(16.dp),
        color = Color.White,
        shadowElevation = 2.dp,
        onClick = onClick
    ) {
        Column(Modifier.fillMaxSize(), verticalArrangement = Arrangement.Center, horizontalAlignment = Alignment.CenterHorizontally) {
            Box(Modifier.size(40.dp).background(service.color.copy(alpha = 0.12f), CircleShape), contentAlignment = Alignment.Center) {
                Icon(service.icon, contentDescription = null, tint = service.color)
            }
            Text(service.label, fontSize = 13.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(top = 8.dp), color = TextPrimary)
        }
    }
}

@Composable
private fun TransactionRow(tx: com.philmoresms.app.network.Transaction) {
    Surface(
        modifier = Modifier.fillMaxWidth().padding(bottom = 12.dp),
        shape = RoundedCornerShape(16.dp),
        color = Color.White,
        shadowElevation = 1.dp
    ) {
        Row(Modifier.padding(16.dp), verticalAlignment = Alignment.CenterVertically) {
            Box(Modifier.size(44.dp).background(Background, RoundedCornerShape(12.dp)), contentAlignment = Alignment.Center) {
                Icon(if (tx.amount > 0) Icons.Default.ArrowUpward else Icons.Default.Sms, contentDescription = null, tint = if (tx.amount > 0) Success else SmsColor)
            }
            Column(Modifier.weight(1f).padding(horizontal = 16.dp)) {
                Text(tx.description, fontSize = 15.sp, fontWeight = FontWeight.Bold, maxLines = 1)
                Text(tx.created_at, fontSize = 12.sp, color = TextSecondary)
            }
            Text("${if (tx.amount > 0) "+" else ""}₦${tx.amount}", fontSize = 16.sp, fontWeight = FontWeight.Bold, color = if (tx.amount > 0) Success else Danger)
        }
    }
}
