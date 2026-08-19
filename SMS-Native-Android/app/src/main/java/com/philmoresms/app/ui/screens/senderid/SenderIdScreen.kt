package com.philmoresms.app.ui.screens.senderid

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.*
import androidx.compose.foundation.lazy.LazyColumn
import androidx.compose.foundation.lazy.items
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.foundation.verticalScroll
import androidx.compose.material.icons.Icons
import androidx.compose.material.icons.filled.Badge
import androidx.compose.material3.*
import androidx.compose.material3.ExperimentalMaterial3Api
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
import com.philmoresms.app.network.SenderId
import com.philmoresms.app.ui.components.AppTopBar
import com.philmoresms.app.ui.components.FintechButton
import com.philmoresms.app.ui.components.FintechInput
import com.philmoresms.app.ui.theme.*
import kotlinx.coroutines.launch
import retrofit2.HttpException
import retrofit2.await

class SenderIdViewModel : ViewModel() {
    var senderIds by mutableStateOf<List<SenderId>>(emptyList())
    var loading by mutableStateOf(false)
    var error by mutableStateOf<String?>(null)
    var success by mutableStateOf<String?>(null)

    var newSenderId by mutableStateOf("")
    var sampleMessage by mutableStateOf("")
    var type by mutableStateOf("sms")

    init { load() }

    fun load() {
        loading = true
        error = null
        viewModelScope.launch {
            try {
                senderIds = RetrofitClient.apiService.getSenderIds().await().senderIds ?: emptyList()
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to load sender IDs"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            } finally {
                loading = false
            }
        }
    }

    fun register() {
        error = null
        success = null
        viewModelScope.launch {
            try {
                val res = RetrofitClient.apiService.requestSenderId(newSenderId, sampleMessage, type).await()
                success = res.message ?: "Sender ID request submitted"
                newSenderId = ""
                sampleMessage = ""
                load()
            } catch (e: HttpException) {
                error = ErrorUtils.getErrorMessage(e) ?: "Failed to submit request"
            } catch (e: Exception) {
                error = e.message ?: "An error occurred"
            }
        }
    }
}

@OptIn(ExperimentalMaterial3Api::class)
@Composable
fun SenderIdScreen(navController: NavController) {
    val viewModel: SenderIdViewModel = viewModel()

    Column(modifier = Modifier.fillMaxSize().background(Background)) {
        AppTopBar("Sender ID", onBack = { navController.popBackStack() })

        Column(
            modifier = Modifier.fillMaxSize().verticalScroll(rememberScrollState()).padding(20.dp)
        ) {
            Text("Register a Sender ID", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = TextPrimary, modifier = Modifier.padding(bottom = 16.dp))
            Text("Type", fontSize = 12.sp, color = TextSecondary, fontWeight = FontWeight.Bold, modifier = Modifier.padding(bottom = 8.dp))
            Row(horizontalArrangement = Arrangement.spacedBy(12.dp), modifier = Modifier.padding(bottom = 16.dp)) {
                FilterChip(selected = viewModel.type == "sms", onClick = { viewModel.type = "sms" }, label = { Text("SMS") })
                FilterChip(selected = viewModel.type == "otp", onClick = { viewModel.type = "otp" }, label = { Text("OTP") })
            }
            FintechInput("Sender ID (max 11 chars)", viewModel.newSenderId, { viewModel.newSenderId = it })
            FintechInput("Sample Message", viewModel.sampleMessage, { viewModel.sampleMessage = it })

            if (viewModel.error != null) Text(viewModel.error ?: "", color = Danger, fontSize = 13.sp, modifier = Modifier.padding(bottom = 12.dp))
            if (viewModel.success != null) Text(viewModel.success ?: "", color = Success, fontSize = 13.sp, modifier = Modifier.padding(bottom = 12.dp))

            FintechButton("Submit Request", { viewModel.register() }, backgroundColor = GlobalColor)

            Text("My Sender IDs", fontSize = 18.sp, fontWeight = FontWeight.Bold, color = TextPrimary, modifier = Modifier.padding(top = 28.dp, bottom = 16.dp))

            if (viewModel.loading) {
                Box(Modifier.fillMaxWidth().padding(24.dp), contentAlignment = Alignment.Center) { CircularProgressIndicator() }
            } else {
                viewModel.senderIds.forEach { sender ->
                    SenderIdRow(sender)
                }
                if (viewModel.senderIds.isEmpty()) {
                    Text("No sender IDs yet.", color = TextSecondary, modifier = Modifier.padding(vertical = 8.dp))
                }
            }
            Spacer(Modifier.height(24.dp))
        }
    }
}

@Composable
private fun SenderIdRow(sender: SenderId) {
    val statusColor = when (sender.status?.lowercase()) {
        "approved", "active" -> Success
        "pending" -> Warning
        "rejected" -> Danger
        else -> TextSecondary
    }
    Surface(
        modifier = Modifier.fillMaxWidth().padding(bottom = 10.dp),
        shape = RoundedCornerShape(14.dp),
        color = Color.White,
        shadowElevation = 1.dp
    ) {
        Row(Modifier.padding(14.dp), verticalAlignment = Alignment.CenterVertically) {
            Icon(Icons.Default.Badge, contentDescription = null, tint = GlobalColor)
            Spacer(Modifier.width(12.dp))
            Column(Modifier.weight(1f)) {
                Text(sender.senderId ?: "-", fontSize = 15.sp, fontWeight = FontWeight.Bold, color = TextPrimary)
                Text(sender.createdAt ?: "", fontSize = 12.sp, color = TextSecondary)
            }
            Surface(shape = RoundedCornerShape(20.dp), color = statusColor.copy(alpha = 0.12f)) {
                Text(sender.status ?: "-", color = statusColor, fontSize = 12.sp, fontWeight = FontWeight.Bold, modifier = Modifier.padding(horizontal = 10.dp, vertical = 4.dp))
            }
        }
    }
}
