package com.philmoresms.app.ui.screens.smshistory

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Sms
import androidx.compose.material3.*
import androidx.compose.runtime.*
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextOverflow
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
import com.philmoresms.app.ui.components.LoadingBox
import com.philmoresms.app.ui.theme.*
import kotlinx.coroutines.launch
import retrofit2.HttpException
import retrofit2.await

class SmsHistoryViewModel : ViewModel() {
    var records by mutableStateOf<List<SmsRecord>>(emptyList())
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)

    fun load() {
        loading = true
        error = null
        viewModelScope.launch {
            try {
                records = RetrofitClient.apiService.getMessages().await().messages ?: emptyList()
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to load SMS history"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            } finally {
                loading = false
            }
        }
    }
}

@Composable
fun SmsHistoryScreen(navController: NavController) {
    val viewModel: SmsHistoryViewModel = viewModel()
    LaunchedEffect(Unit) { viewModel.load() }

    Column(modifier = Modifier.fillMaxSize().background(Background)) {
        AppTopBar("SMS History", onBack = { navController.popBackStack() })

        when {
            viewModel.loading -> LoadingBox()
            viewModel.error != null -> Box(Modifier.fillMaxSize(), contentAlignment = Alignment.Center) {
                Text(viewModel.error ?: "", color = Danger, modifier = Modifier.padding(24.dp))
            }
            else -> LazyColumn(
                modifier = Modifier.fillMaxSize(),
                contentPadding = PaddingValues(16.dp),
                verticalArrangement = Arrangement.spacedBy(12.dp)
            ) {
                items(viewModel.records) { record -> SmsRecordRow(record) }
            }
        }
    }
}

@Composable
private fun SmsRecordRow(record: SmsRecord) {
    val statusColor = when (record.status?.lowercase()) {
        "success", "delivered" -> Success
        "failed", "failure" -> Danger
        else -> Warning
    }
    Surface(
        modifier = Modifier.fillMaxWidth(),
        shape = RoundedCornerShape(16.dp),
        color = Color.White,
        shadowElevation = 1.dp
    ) {
        Column(Modifier.padding(16.dp)) {
            Row(verticalAlignment = Alignment.CenterVertically) {
                Icon(Icons.Default.Sms, contentDescription = null, tint = SmsColor, modifier = Modifier.size(20.dp))
                Spacer(Modifier.width(8.dp))
                Text("From: ${record.senderId ?: "-"}", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = TextPrimary, maxLines = 1, overflow = TextOverflow.Ellipsis)
                Spacer(Modifier.weight(1f))
                Surface(shape = RoundedCornerShape(20.dp), color = statusColor.copy(alpha = 0.12f)) {
                    Text(record.status ?: "-", color = statusColor, fontSize = 12.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(horizontal = 10.dp, vertical = 4.dp))
                }
            }
            Text(record.message ?: "", fontSize = 13.sp, color = TextSecondary, maxLines = 2, overflow = TextOverflow.Ellipsis, modifier = Modifier.padding(top = 8.dp))
            Row(modifier = Modifier.fillMaxWidth().padding(top = 10.dp), horizontalArrangement = Arrangement.SpaceBetween) {
                Text("To: ${record.recipients ?: "-"}", fontSize = 12.sp, color = TextSecondary, maxLines = 1, overflow = TextOverflow.Ellipsis, modifier = Modifier.weight(1f))
                Text("₦${record.cost ?: 0.0}", fontSize = 14.sp, fontWeight = FontWeight.Bold, color = TextPrimary)
            }
            Text(record.createdAt ?: "", fontSize = 11.sp, color = TextSecondary, modifier = Modifier.padding(top = 6.dp))
        }
    }
}
