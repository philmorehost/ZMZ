package com.philmoresms.app.ui.screens.reports

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import androidx.lifecycle.viewmodel.compose.viewModel
import androidx.navigation.NavController
import com.philmoresms.app.network.ErrorUtils
import com.philmoresms.app.network.RetrofitClient
import com.philmoresms.app.network.SmsRecord
import com.philmoresms.app.ui.components.AppTopBar
import com.philmoresms.app.ui.components.FooterBar
import com.philmoresms.app.ui.components.LoadingBox
import com.philmoresms.app.ui.theme.*
import kotlinx.coroutines.launch
import retrofit2.HttpException
import retrofit2.await

class ReportsViewModel : ViewModel() {
    var records by mutableStateOf<List<SmsRecord>>(emptyList())
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)

    init { load() }

    fun load() {
        loading = true
        error = null
        viewModelScope.launch {
            try {
                records = RetrofitClient.apiService.getMessages().await().messages ?: emptyList()
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to load report"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            } finally {
                loading = false
            }
        }
    }
}

@Composable
fun ReportsScreen(navController: NavController) {
    val viewModel: ReportsViewModel = viewModel()

    Scaffold(
        bottomBar = {
            FooterBar("SMS Report") { label ->
                when (label) {
                    "Home" -> navController.navigate("home") { popUpTo("home") { inclusive = true } }
                    "Support" -> navController.navigate("support")
                }
            }
        }
    ) { padding ->
        Column(modifier = Modifier.fillMaxSize().padding(padding).background(Background)) {
            AppTopBar("SMS Report")

            if (viewModel.loading) {
                LoadingBox()
            } else {
                val total = viewModel.records.size
                val totalCost = viewModel.records.sumOf { it.cost ?: 0.0 }
                val delivered = viewModel.records.count { it.status?.lowercase() == "success" || it.status?.lowercase() == "delivered" }
                val failed = viewModel.records.count { it.status?.lowercase() == "failed" }

                Row(Modifier.fillMaxWidth().padding(horizontal = 16.dp), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    StatCard("Total Sent", "$total", SmsColor, Modifier.weight(1f))
                    StatCard("Delivered", "$delivered", Success, Modifier.weight(1f))
                }
                Row(Modifier.fillMaxWidth().padding(horizontal = 16.dp, vertical = 10.dp), horizontalArrangement = Arrangement.spacedBy(10.dp)) {
                    StatCard("Failed", "$failed", Danger, Modifier.weight(1f))
                    StatCard("Total Cost", "₦$totalCost", Primary, Modifier.weight(1f))
                }

                LazyColumn(Modifier.fillMaxSize(), contentPadding = PaddingValues(16.dp), verticalArrangement = Arrangement.spacedBy(10.dp)) {
                    items(viewModel.records) { record ->
                        Surface(
                            modifier = Modifier.fillMaxWidth(),
                            shape = RoundedCornerShape(14.dp),
                            color = Color.White,
                            shadowElevation = 1.dp
                        ) {
                            Column(Modifier.padding(14.dp)) {
                                Row(Modifier.fillMaxWidth(), horizontalArrangement = Arrangement.SpaceBetween) {
                                    Text("From: ${record.senderId ?: "-"}", fontSize = 13.sp, fontWeight = FontWeight.Bold, color = TextPrimary)
                                    Text("₦${record.cost ?: 0.0}", fontSize = 13.sp, fontWeight = FontWeight.Bold, color = Primary)
                                }
                                Text(record.message ?: "", fontSize = 13.sp, color = TextSecondary, maxLines = 2, modifier = Modifier.padding(top = 6.dp))
                                Row(Modifier.fillMaxWidth().padding(top = 8.dp), horizontalArrangement = Arrangement.SpaceBetween) {
                                    Text(record.createdAt ?: "", fontSize = 11.sp, color = TextSecondary)
                                    Text(record.status ?: "", fontSize = 12.sp, fontWeight = FontWeight.Bold, color = if (record.status?.lowercase() == "failed") Danger else Success)
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

@Composable
private fun StatCard(label: String, value: String, color: Color, modifier: Modifier = Modifier) {
    Surface(modifier = modifier, shape = RoundedCornerShape(16.dp), color = Color.White, shadowElevation = 1.dp) {
        Column(Modifier.padding(16.dp)) {
            Text(label, fontSize = 12.sp, color = TextSecondary, fontWeight = FontWeight.Bold)
            Text(value, fontSize = 22.sp, fontWeight = FontWeight.ExtraBold, color = color, modifier = Modifier.padding(top = 6.dp))
        }
    }
}
